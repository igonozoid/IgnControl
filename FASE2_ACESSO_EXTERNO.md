# Fase 2 — Acesso externo (consulta) via Cloudflare Tunnel

Objetivo combinado: você e os sócios conseguem consultar o sistema de fora
da LAN, com dados em tempo real (não uma cópia atrasada), sem abrir porta
no roteador e sem VPN pra instalar. Acesso é só de leitura/consulta.

Como funciona, em uma frase: o `cloudflared` (um programinha da Cloudflare)
roda no mesmo PC/servidor onde o XAMPP já está rodando, e cria uma conexão
de saída até a Cloudflare — não precisa abrir nada no roteador. A
Cloudflare te dá um endereço `https://algumacoisa.seudominio.com` que,
quando acessado, é encaminhado até o `localhost:8000` (ou a porta que o
XAMPP estiver usando) do seu PC.

## 0. Pré-requisito: um domínio na Cloudflare

Precisa de um domínio cadastrado na Cloudflare (o registro em si pode
continuar sendo em outro lugar — GoDaddy, Registro.br, o próprio HostGator
— só os *nameservers* apontam pra Cloudflare). Isso é gratuito.

- Se você já tem um domínio (ex. do HostGator), dá pra usar: cria conta
  gratuita em https://dash.cloudflare.com, adiciona esse domínio lá, e
  troca os nameservers no registrador pra os que a Cloudflare indicar.
- Se não tem domínio nenhum ainda, me avisa que a gente vê a opção mais
  barata de registrar um.

Isso não interfere no HostGator continuar hospedando seu site atual, se
houver um — é só o DNS que passa a ser gerenciado pela Cloudflare.

## 1. Instalar o cloudflared no Windows

```powershell
winget install --id Cloudflare.cloudflared
```

(Se não tiver `winget`, baixa o instalador em
https://github.com/cloudflare/cloudflared/releases — arquivo
`cloudflared-windows-amd64.msi`.)

## 2. Autenticar e criar o túnel

```powershell
cloudflared tunnel login
```

Isso abre o navegador pra você escolher o domínio (o mesmo que colocou na
Cloudflare no passo 0) e autorizar.

```powershell
cloudflared tunnel create ignf-financeiro
```

Isso vai imprimir um `Tunnel ID` e criar um arquivo de credenciais — guarda
o caminho que ele mostrar.

## 3. Configurar o túnel

Cria o arquivo `%USERPROFILE%\.cloudflared\config.yml`:

```yaml
tunnel: <TUNNEL_ID_QUE_APARECEU_NO_PASSO_2>
credentials-file: C:\Users\igono\.cloudflared\<TUNNEL_ID>.json

ingress:
  - hostname: financeiro.seudominio.com
    service: http://localhost:8000
  - service: http_status:404
```

Depois aponta o DNS pro túnel:

```powershell
cloudflared tunnel route dns ignf-financeiro financeiro.seudominio.com
```

E roda o túnel:

```powershell
cloudflared tunnel run ignf-financeiro
```

Se quiser que ele fique sempre rodando (mesmo depois de reiniciar o PC),
me avisa que eu te passo o comando pra instalar como serviço do Windows
(`cloudflared service install`).

## 4. Camada extra de segurança: Cloudflare Access

Isso é o que garante que só você e os sócios conseguem sequer *chegar* na
tela de login do sistema — antes de tocar no Laravel, a Cloudflare pede
pra pessoa confirmar o e-mail dela (recebe um código, digita, entra).
Assim, mesmo alguém adivinhando a URL, não passa daqui sem estar na sua
lista.

Painel Cloudflare → **Zero Trust** → **Access** → **Applications** →
**Add an application** → tipo **Self-hosted**:

- **Application domain**: `financeiro.seudominio.com`
- **Policy**: "Allow" pra uma lista de e-mails (o seu e o dos sócios)

Isso é gratuito até 50 usuários no plano free da Cloudflare — bem acima do
que você precisa.

## 5. No sistema: criar os acessos dos sócios

Isso já está pronto no sistema (tela **Administração → Usuários**). Pra
cada sócio: convida com o e-mail dele, e no nível de acesso por módulo eu
sugiro:

- **Financeiro**: Leitura
- **Contatos**: Leitura
- **Relatórios**: Leitura
- **Auditoria**: Nenhum
- **Administração**: Nenhum

Com "Leitura" eles veem tudo mas não conseguem criar, editar ou excluir
nada — a decisão de quem recebe o quê é sua, isso aqui é só a sugestão
padrão pra sócio-consulta.

## 6. Checklist antes de liberar pros sócios

- [ ] `.env` do sistema com `APP_DEBUG=false` (importante: com `true`,
      qualquer erro mostra detalhes internos do sistema na tela — ok em
      desenvolvimento, mas não pode ficar assim com acesso externo ligado)
- [ ] Túnel rodando e `https://financeiro.seudominio.com` abrindo a tela
      de login do sistema
- [ ] Cloudflare Access configurado e testado (tenta acessar de um
      navegador anônimo/outro e-mail — tem que barrar)
- [ ] Conta de cada sócio criada com nível "Leitura" nos módulos certos
- [ ] Testar login de um sócio de verdade, de fora da rede (ex. 4G do
      celular), confirmando que só enxerga o que devia

## O que eu já ajustei no código

Adicionei uma configuração (`trustProxies`) no `bootstrap/app.php` pra o
Laravel entender corretamente que a conexão chegando pelo túnel é HTTPS,
evitando problema de sessão/links quebrados pra quem acessa de fora. Isso
já está commitado — não precisa fazer nada além do checklist acima.
