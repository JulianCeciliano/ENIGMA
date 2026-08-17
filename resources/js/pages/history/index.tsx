import { Head, useHttp } from '@inertiajs/react';
import { useState } from 'react';
import EnigmaShell from '@/components/enigma-shell';
import apiHistory from '@/routes/api/history';
import type { HistoryOperation, Paginated } from '@/types/ciphers';

type HistoryProps = {
    operations: Paginated<HistoryOperation>;
};

type HistoryFilters = {
    operation: string;
    algorithm: string;
    dateRange: string;
    search: string;
};

const operationLabels: Record<string, string> = {
    ENCRYPT: 'CIFRAR',
    DECRYPT: 'DESCIFRAR',
    BREAK: 'ROMPER',
};

const statusLabels: Record<string, string> = {
    SUCCESS: 'ÉXITO',
    FAILED: 'FALLIDO',
};

export default function History({
    operations: initialOperations,
}: HistoryProps) {
    const [operations, setOperations] = useState(initialOperations);
    const [filters, setFilters] = useState<HistoryFilters>({
        operation: 'all',
        algorithm: 'all',
        dateRange: 'all',
        search: '',
    });
    const request = useHttp<HistoryFilters, Paginated<HistoryOperation>>({
        ...filters,
    });

    function queryUrl(): string {
        const query = Object.fromEntries(
            Object.entries(filters).filter(
                ([, value]) => value && value !== 'all',
            ),
        );

        return apiHistory.index.url({ query });
    }

    function updateFilter(name: keyof HistoryFilters, value: string): void {
        const nextFilters = { ...filters, [name]: value };

        setFilters(nextFilters);
        request.setData(nextFilters);
    }

    function applyFilters(event: React.FormEvent<HTMLFormElement>): void {
        event.preventDefault();
        request.get(queryUrl(), {
            onSuccess: (response) => setOperations(response),
        });
    }

    function clearLogs(): void {
        if (!window.confirm('¿Eliminar todo el historial de este operador?')) {
            return;
        }

        request.delete(apiHistory.clear.url(), {
            onSuccess: () =>
                setOperations({
                    ...operations,
                    data: [],
                    total: 0,
                    from: null,
                    to: null,
                    last_page: 1,
                    current_page: 1,
                }),
        });
    }

    return (
        <EnigmaShell active="history" title="Registros del sistema">
            <Head title="Historial de operaciones" />
            <header className="mb-8 flex flex-col gap-5 border-b border-outline-variant/30 pb-8 md:flex-row md:items-end md:justify-between">
                <div>
                    <div className="mb-2 font-code text-[10px] tracking-[0.2em] text-tertiary uppercase">
                        ENIGMA / OPERACIONES PERSISTIDAS
                    </div>
                    <h1 className="font-headline text-3xl font-bold tracking-tight sm:text-4xl">
                        Registros del sistema
                    </h1>
                    <p className="mt-3 font-code text-sm text-on-surface-variant">
                        Registro cronológico de las operaciones criptográficas
                        recientes.
                    </p>
                </div>
                <div className="flex gap-3">
                    <button
                        className="flex items-center gap-2 border border-outline-variant/30 px-4 py-2 font-code text-xs tracking-wider text-on-surface uppercase transition-colors hover:border-tertiary hover:text-tertiary"
                        onClick={() => window.print()}
                        type="button"
                    >
                        <span className="material-symbols-outlined text-base">
                            download
                        </span>
                        Exportar
                    </button>
                    <button
                        className="flex items-center gap-2 border border-error/30 bg-[#1a1919] px-4 py-2 font-code text-xs tracking-wider text-error uppercase transition-colors hover:bg-error hover:text-primary-container"
                        onClick={clearLogs}
                        type="button"
                    >
                        <span className="material-symbols-outlined text-base">
                            delete
                        </span>
                        Vaciar registros
                    </button>
                </div>
            </header>

            <form
                className="mb-6 flex flex-col gap-4 border border-outline-variant/30 bg-surface-container-low p-4 md:flex-row md:items-end"
                onSubmit={applyFilters}
            >
                <label className="flex flex-1 flex-col gap-2">
                    <span className="font-code text-[10px] tracking-widest text-on-surface-variant uppercase">
                        Tipo de operación
                    </span>
                    <select
                        className="border border-outline-variant/30 bg-[#1a1919] p-2 font-code text-xs text-on-surface outline-none focus:border-tertiary"
                        onChange={(event) =>
                            updateFilter('operation', event.target.value)
                        }
                        value={filters.operation}
                    >
                        <option value="all">TODAS LAS OPERACIONES</option>
                        <option value="encrypt">CIFRAR</option>
                        <option value="decrypt">DESCIFRAR</option>
                        <option value="break">ROMPER</option>
                    </select>
                </label>
                <label className="flex flex-1 flex-col gap-2">
                    <span className="font-code text-[10px] tracking-widest text-on-surface-variant uppercase">
                        Rango de fechas
                    </span>
                    <select
                        className="border border-outline-variant/30 bg-[#1a1919] p-2 font-code text-xs text-on-surface outline-none focus:border-tertiary"
                        onChange={(event) =>
                            updateFilter('dateRange', event.target.value)
                        }
                        value={filters.dateRange}
                    >
                        <option value="all">TODO EL PERIODO</option>
                        <option value="24h">ÚLTIMAS 24 HORAS</option>
                        <option value="7d">ÚLTIMOS 7 DÍAS</option>
                        <option value="30d">ÚLTIMOS 30 DÍAS</option>
                    </select>
                </label>
                <label className="flex flex-1 flex-col gap-2">
                    <span className="font-code text-[10px] tracking-widest text-on-surface-variant uppercase">
                        Buscar hash
                    </span>
                    <input
                        className="border border-outline-variant/30 bg-[#1a1919] p-2 font-code text-xs text-on-surface outline-none placeholder:text-on-surface-variant/50 focus:border-tertiary"
                        onChange={(event) =>
                            updateFilter('search', event.target.value)
                        }
                        placeholder="prefijo sha256..."
                        value={filters.search}
                    />
                </label>
                <button
                    className="bg-tertiary px-5 py-2 font-code text-xs font-bold tracking-wider text-primary-container uppercase hover:bg-[#a99985]"
                    type="submit"
                >
                    Aplicar
                </button>
            </form>

            <section className="overflow-x-auto border border-outline-variant/30 bg-primary-container">
                <table className="w-full min-w-[850px] border-collapse text-left">
                    <thead className="border-b border-outline-variant/30 bg-surface-container-high">
                        <tr>
                            {[
                                'FECHA [UTC]',
                                'OPERACIÓN',
                                'ALGORITMO',
                                'ESTADO',
                                'VISTA DEL PAYLOAD',
                                'DURACIÓN',
                            ].map((heading) => (
                                <th
                                    className="px-4 py-3 font-code text-[10px] font-medium tracking-wider text-on-surface-variant uppercase"
                                    key={heading}
                                >
                                    {heading}
                                </th>
                            ))}
                        </tr>
                    </thead>
                    <tbody className="font-code text-xs">
                        {operations.data.map((operation) => (
                            <tr
                                className="border-b border-outline-variant/20 transition-colors hover:bg-surface-container-low"
                                key={operation.id}
                            >
                                <td className="px-4 py-4 text-on-surface-variant">
                                    {operation.timestamp
                                        ? new Date(operation.timestamp)
                                              .toISOString()
                                              .replace('T', ' ')
                                              .slice(0, 19)
                                        : '--'}
                                </td>
                                <td className="px-4 py-4 text-secondary">
                                    {operationLabels[operation.operation] ??
                                        operation.operation}
                                </td>
                                <td className="px-4 py-4 text-on-surface">
                                    {operation.algorithm}
                                </td>
                                <td
                                    className={`px-4 py-4 ${operation.status === 'SUCCESS' ? 'text-tertiary' : 'text-error'}`}
                                >
                                    <span className="mr-2 inline-block h-2 w-2 bg-current" />
                                    {statusLabels[operation.status] ??
                                        operation.status}
                                </td>
                                <td className="max-w-[260px] truncate px-4 py-4 text-on-surface-variant">
                                    {operation.payloadPreview ||
                                        operation.errorMessage ||
                                        '--'}
                                </td>
                                <td className="px-4 py-4 text-on-surface-variant">
                                    {operation.durationMs ?? '--'} ms
                                </td>
                            </tr>
                        ))}
                        {operations.data.length === 0 && (
                            <tr>
                                <td
                                    className="px-4 py-16 text-center font-code text-xs tracking-widest text-outline uppercase"
                                    colSpan={6}
                                >
                                    Sin registros de operaciones
                                </td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </section>

            <div className="mt-4 flex flex-col gap-3 border-t border-outline-variant/30 pt-4 font-code text-xs text-on-surface-variant sm:flex-row sm:items-center sm:justify-between">
                <span>
                    Mostrando {operations.from ?? 0}-{operations.to ?? 0} de{' '}
                    {operations.total} registros
                </span>
                <div className="flex gap-2">
                    <button
                        className="border border-outline-variant/30 px-3 py-2 hover:border-tertiary hover:text-tertiary disabled:opacity-30"
                        disabled={operations.current_page <= 1}
                        onClick={() => {
                            const page = operations.current_page - 1;
                            request.get(
                                apiHistory.index.url({ query: { page } }),
                                {
                                    onSuccess: (response) =>
                                        setOperations(response),
                                },
                            );
                        }}
                        type="button"
                    >
                        &lt; ANTERIOR
                    </button>
                    <button
                        className="border border-outline-variant/30 px-3 py-2 hover:border-tertiary hover:text-tertiary disabled:opacity-30"
                        disabled={
                            operations.current_page >= operations.last_page
                        }
                        onClick={() => {
                            const page = operations.current_page + 1;
                            request.get(
                                apiHistory.index.url({ query: { page } }),
                                {
                                    onSuccess: (response) =>
                                        setOperations(response),
                                },
                            );
                        }}
                        type="button"
                    >
                        SIGUIENTE &gt;
                    </button>
                </div>
            </div>
        </EnigmaShell>
    );
}
