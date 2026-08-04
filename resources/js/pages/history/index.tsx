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
        <EnigmaShell active="history" title="System Logs">
            <Head title="Operation History" />
            <header className="mb-8 flex flex-col gap-5 border-b border-outline-variant/30 pb-8 md:flex-row md:items-end md:justify-between">
                <div>
                    <div className="mb-2 font-code text-[10px] tracking-[0.2em] text-tertiary uppercase">
                        ENIGMA / PERSISTED OPERATIONS
                    </div>
                    <h1 className="font-headline text-3xl font-bold tracking-tight sm:text-4xl">
                        System Logs
                    </h1>
                    <p className="mt-3 font-code text-sm text-on-surface-variant">
                        Chronological record of recent cryptographic operations.
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
                        Export
                    </button>
                    <button
                        className="flex items-center gap-2 border border-error/30 bg-[#1a1919] px-4 py-2 font-code text-xs tracking-wider text-error uppercase transition-colors hover:bg-error hover:text-primary-container"
                        onClick={clearLogs}
                        type="button"
                    >
                        <span className="material-symbols-outlined text-base">
                            delete
                        </span>
                        Clear Logs
                    </button>
                </div>
            </header>

            <form
                className="mb-6 flex flex-col gap-4 border border-outline-variant/30 bg-surface-container-low p-4 md:flex-row md:items-end"
                onSubmit={applyFilters}
            >
                <label className="flex flex-1 flex-col gap-2">
                    <span className="font-code text-[10px] tracking-widest text-on-surface-variant uppercase">
                        Operation Type
                    </span>
                    <select
                        className="border border-outline-variant/30 bg-[#1a1919] p-2 font-code text-xs text-on-surface outline-none focus:border-tertiary"
                        onChange={(event) =>
                            updateFilter('operation', event.target.value)
                        }
                        value={filters.operation}
                    >
                        <option value="all">ALL OPERATIONS</option>
                        <option value="encrypt">ENCRYPT</option>
                        <option value="decrypt">DECRYPT</option>
                        <option value="break">BREAK</option>
                    </select>
                </label>
                <label className="flex flex-1 flex-col gap-2">
                    <span className="font-code text-[10px] tracking-widest text-on-surface-variant uppercase">
                        Date Range
                    </span>
                    <select
                        className="border border-outline-variant/30 bg-[#1a1919] p-2 font-code text-xs text-on-surface outline-none focus:border-tertiary"
                        onChange={(event) =>
                            updateFilter('dateRange', event.target.value)
                        }
                        value={filters.dateRange}
                    >
                        <option value="all">ALL TIME</option>
                        <option value="24h">LAST 24 HOURS</option>
                        <option value="7d">LAST 7 DAYS</option>
                        <option value="30d">LAST 30 DAYS</option>
                    </select>
                </label>
                <label className="flex flex-1 flex-col gap-2">
                    <span className="font-code text-[10px] tracking-widest text-on-surface-variant uppercase">
                        Search Hash
                    </span>
                    <input
                        className="border border-outline-variant/30 bg-[#1a1919] p-2 font-code text-xs text-on-surface outline-none placeholder:text-on-surface-variant/50 focus:border-tertiary"
                        onChange={(event) =>
                            updateFilter('search', event.target.value)
                        }
                        placeholder="sha256 prefix..."
                        value={filters.search}
                    />
                </label>
                <button
                    className="bg-tertiary px-5 py-2 font-code text-xs font-bold tracking-wider text-primary-container uppercase hover:bg-[#a99985]"
                    type="submit"
                >
                    Apply
                </button>
            </form>

            <section className="overflow-x-auto border border-outline-variant/30 bg-primary-container">
                <table className="w-full min-w-[850px] border-collapse text-left">
                    <thead className="border-b border-outline-variant/30 bg-surface-container-high">
                        <tr>
                            {[
                                'TIMESTAMP [UTC]',
                                'OP_TYPE',
                                'ALGORITHM',
                                'STATUS',
                                'PAYLOAD_PREVIEW',
                                'DURATION',
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
                                    {operation.operation}
                                </td>
                                <td className="px-4 py-4 text-on-surface">
                                    {operation.algorithm}
                                </td>
                                <td
                                    className={`px-4 py-4 ${operation.status === 'SUCCESS' ? 'text-tertiary' : 'text-error'}`}
                                >
                                    <span className="mr-2 inline-block h-2 w-2 bg-current" />
                                    {operation.status}
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
                                    No operation records
                                </td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </section>

            <div className="mt-4 flex flex-col gap-3 border-t border-outline-variant/30 pt-4 font-code text-xs text-on-surface-variant sm:flex-row sm:items-center sm:justify-between">
                <span>
                    Showing {operations.from ?? 0}-{operations.to ?? 0} of{' '}
                    {operations.total} entries
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
                        &lt; PREV
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
                        NEXT &gt;
                    </button>
                </div>
            </div>
        </EnigmaShell>
    );
}
