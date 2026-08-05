# Produção — LAN + acesso externo (notas para quando for publicar)

Anotações da conversa sobre como o sistema vai rodar quando sair do
ambiente de desenvolvimento (XAMPP no seu PC) para uso real, em rede
local com um túnel para acesso externo.

## Acesso remoto com escrita, não só leitura

O sistema já tem controle de permissão por módulo e por usuário
(model `Permission`, três níveis: nenhum/leitura/total). Isso vale do
mesmo jeito para quem acessa pela LAN ou pelo túnel externo — não é
"a rede externa só lê", é "cada usuário faz o que a permissão dele
permite", esteja ele na LAN ou fora. Então dá para liberar escrita
remota normalmente, sem precisar de nada especial no código.

Checklist de segurança para quando for expor pelo túnel:

- `.env` de produção com `APP_DEBUG=false` (senão erros mostram
  detalhes internos do sistema para qualquer um que acessar de fora).
- Túnel com HTTPS (Cloudflare Tunnel e ngrok já fazem isso por padrão).
- `APP_KEY` de produção diferente da de desenvolvimento (gerar de novo
  com `php artisan key:generate` no ambiente final).

## Como deixar o servidor e o túnel rodando sempre

**Não usar `php artisan serve` para isso.** Esse comando é só um
servidor de desenvolvimento — single-threaded, não pensado para vários
usuários acessando ao mesmo tempo, e some se o terminal fechar.

Recomendação: usar o **Apache do XAMPP** (que já está instalado) como
servidor de verdade, apontando o virtual host para a pasta `public/`
do projeto, e configurar o Apache/MySQL do XAMPP para rodar **como
serviço do Windows** (o próprio painel de controle do XAMPP tem essa
opção — checkbox "Service" ao lado de Apache e MySQL). Isso já resolve
"iniciar sozinho com o Windows" sem precisar de `.bat` nenhum, e
reinicia sozinho se cair, o que um `.bat` na pasta de Inicialização
não faz.

Para o túnel, mesma lógica — evitar deixar dependente de um terminal
aberto:

- **Cloudflare Tunnel**: tem instalação nativa como serviço Windows
  (`cloudflared service install`), recomendado.
- **ngrok**: não tem modo serviço nativo; se for usar, precisa de um
  wrapper tipo NSSM (Non-Sucking Service Manager) para rodar como
  serviço. Cloudflare Tunnel é mais simples nesse ponto.

Resumindo: `.bat` na inicialização funciona, mas é a opção mais frágil
(só inicia depois do login do Windows, não reinicia sozinho se travar,
janela de terminal fica exposta). Serviço do Windows (via XAMPP para
Apache/MySQL, via `cloudflared service install` para o túnel) é mais
robusto para um servidor que precisa ficar sempre no ar.

Quando chegar a hora de configurar isso de fato, revisamos juntos o
passo a passo específico da ferramenta de túnel escolhida.
