import { Head, Link } from '@inertiajs/react';
import EnigmaShell from '@/components/enigma-shell';
import ciphersRoutes from '@/routes/ciphers';
import type { CipherMetadata } from '@/types/ciphers';

type CatalogProps = {
    ciphers: CipherMetadata[];
};

const icons: Record<string, string> = {
    affine: 'function',
    atbash: 'swap_horiz',
    autokey: 'key',
    beaufort: 'waves',
    caesar: 'sync_alt',
    columnar: 'view_column',
    'rail-fence': 'route',
    rot13: 'autorenew',
    substitution: 'apps',
    vigenere: 'vpn_key',
};

function methodType(cipher: CipherMetadata): string {
    if (['rail-fence', 'columnar'].includes(cipher.id)) {
        return 'Transposition';
    }

    if (['vigenere', 'beaufort', 'autokey'].includes(cipher.id)) {
        return 'Polyalphabetic';
    }

    return 'Substitution';
}

export default function Catalog({ ciphers }: CatalogProps) {
    return (
        <EnigmaShell active="catalog" title="Methods Catalog">
            <Head title="Methods Catalog" />
            <header className="mb-10 border-b border-outline-variant/30 pb-8">
                <div className="border-l-2 border-tertiary pl-4">
                    <div className="mb-2 font-code text-[10px] tracking-[0.2em] text-tertiary uppercase">
                        ENIGMA / METHOD REGISTRY
                    </div>
                    <h1 className="font-headline text-3xl font-bold tracking-tight sm:text-4xl">
                        Methods Catalog
                    </h1>
                    <p className="mt-3 max-w-3xl font-code text-sm leading-relaxed text-on-surface-variant">
                        Select a cryptographic algorithm for the current
                        session. Every method is historical, inspectable and
                        intended for laboratory learning only.
                    </p>
                </div>
            </header>

            <div className="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-3">
                {ciphers.map((cipher) => (
                    <article
                        className="group relative flex flex-col overflow-hidden border border-outline-variant/30 bg-surface-container p-6 transition-colors hover:border-tertiary"
                        key={cipher.id}
                    >
                        <span className="pointer-events-none absolute top-4 right-4 font-code text-6xl text-tertiary opacity-10 transition-opacity group-hover:opacity-20">
                            {cipher.id.slice(0, 2).toUpperCase()}
                        </span>
                        <div className="relative flex items-center gap-3 border-b border-outline-variant/30 pb-4">
                            <span className="material-symbols-outlined text-tertiary">
                                {icons[cipher.id] ?? 'code'}
                            </span>
                            <h2 className="font-code text-xs font-semibold tracking-wider text-primary uppercase">
                                {cipher.name}
                            </h2>
                        </div>
                        <div className="relative flex flex-1 flex-col gap-5 pt-5">
                            <p className="min-h-16 font-body text-sm leading-relaxed text-on-surface-variant">
                                {cipher.description}
                            </p>
                            <dl className="border border-outline-variant/30 bg-[#1a1919] p-3 font-code text-[11px] text-secondary">
                                <div className="flex justify-between border-b border-outline-variant/30 pb-2">
                                    <dt>TYPE</dt>
                                    <dd>{methodType(cipher)}</dd>
                                </div>
                                <div className="flex justify-between border-b border-outline-variant/30 py-2">
                                    <dt>PARAMETERS</dt>
                                    <dd>
                                        {cipher.parameters.length || 'NONE'}
                                    </dd>
                                </div>
                                <div className="flex justify-between pt-2">
                                    <dt>FORMULA</dt>
                                    <dd className="max-w-[55%] truncate text-right">
                                        {cipher.formula}
                                    </dd>
                                </div>
                            </dl>
                            <Link
                                className="mt-auto flex w-full items-center justify-center gap-2 border border-outline px-4 py-3 font-code text-xs font-semibold tracking-widest text-on-surface uppercase transition-colors hover:border-tertiary hover:bg-surface-container-high"
                                href={ciphersRoutes.encrypt()}
                            >
                                Select Method
                                <span className="material-symbols-outlined text-base">
                                    arrow_forward
                                </span>
                            </Link>
                        </div>
                    </article>
                ))}
            </div>
        </EnigmaShell>
    );
}
