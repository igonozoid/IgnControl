@php $cost = $this->cost; @endphp
<div class="bg-white dark:bg-neutral-800 shadow-sm rounded-lg p-4">
    <h2 class="text-xs font-semibold text-gray-900 dark:text-neutral-100 mb-1">Custo/Encargos estimado</h2>
    <p class="text-xs text-gray-500 dark:text-neutral-400 mb-4">
        Calculado a partir do salário mais recente e da alíquota INSS informada no perfil — FGTS 8%, 13º e 1/3 de
        férias provisionados mensalmente (1/12 e 1/36). É uma estimativa pra planejamento, não substitui o cálculo
        da contabilidade/eSocial.
    </p>

    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-2 text-xs">
        <div class="flex justify-between border-b border-gray-100 dark:border-neutral-700 py-1.5"><dt class="text-gray-500 dark:text-neutral-400">Salário base considerado</dt><dd class="font-medium text-gray-900 dark:text-neutral-100">R$ {{ number_format($cost['base_salary'], 2, ',', '.') }}</dd></div>
        <div class="flex justify-between border-b border-gray-100 dark:border-neutral-700 py-1.5"><dt class="text-gray-500 dark:text-neutral-400">Alíquota INSS</dt><dd class="font-medium text-gray-900 dark:text-neutral-100">{{ number_format($cost['inss_rate'] * 100, 2, ',', '.') }}%</dd></div>
        <div class="flex justify-between border-b border-gray-100 dark:border-neutral-700 py-1.5"><dt class="text-gray-500 dark:text-neutral-400">INSS patronal</dt><dd class="font-medium text-gray-900 dark:text-neutral-100">R$ {{ number_format($cost['inss'], 2, ',', '.') }}</dd></div>
        <div class="flex justify-between border-b border-gray-100 dark:border-neutral-700 py-1.5"><dt class="text-gray-500 dark:text-neutral-400">FGTS (8%)</dt><dd class="font-medium text-gray-900 dark:text-neutral-100">R$ {{ number_format($cost['fgts'], 2, ',', '.') }}</dd></div>
        <div class="flex justify-between border-b border-gray-100 dark:border-neutral-700 py-1.5"><dt class="text-gray-500 dark:text-neutral-400">Provisão 13º (1/12)</dt><dd class="font-medium text-gray-900 dark:text-neutral-100">R$ {{ number_format($cost['thirteenth_provision'], 2, ',', '.') }}</dd></div>
        <div class="flex justify-between border-b border-gray-100 dark:border-neutral-700 py-1.5"><dt class="text-gray-500 dark:text-neutral-400">INSS + FGTS sobre 13º</dt><dd class="font-medium text-gray-900 dark:text-neutral-100">R$ {{ number_format($cost['inss_on_thirteenth'] + $cost['fgts_on_thirteenth'], 2, ',', '.') }}</dd></div>
        <div class="flex justify-between border-b border-gray-100 dark:border-neutral-700 py-1.5"><dt class="text-gray-500 dark:text-neutral-400">Provisão 1/3 férias (1/36)</dt><dd class="font-medium text-gray-900 dark:text-neutral-100">R$ {{ number_format($cost['vacation_third_provision'], 2, ',', '.') }}</dd></div>
        <div class="flex justify-between border-b border-gray-100 dark:border-neutral-700 py-1.5"><dt class="text-gray-500 dark:text-neutral-400">INSS + FGTS sobre férias</dt><dd class="font-medium text-gray-900 dark:text-neutral-100">R$ {{ number_format($cost['inss_on_vacation'] + $cost['fgts_on_vacation'], 2, ',', '.') }}</dd></div>
    </dl>

    <div class="mt-4 pt-3 border-t border-gray-200 dark:border-neutral-700 flex justify-between text-sm">
        <span class="font-semibold text-gray-700 dark:text-neutral-300">Total de encargos</span>
        <span class="font-semibold text-gray-900 dark:text-neutral-100">R$ {{ number_format($cost['total_charges'], 2, ',', '.') }}</span>
    </div>
    <div class="mt-1 flex justify-between text-sm">
        <span class="font-semibold text-gray-700 dark:text-neutral-300">Custo total estimado (salário + encargos)</span>
        <span class="font-semibold text-green-700 dark:text-green-400">R$ {{ number_format($cost['total_cost'], 2, ',', '.') }}</span>
    </div>
</div>
