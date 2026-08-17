import { Link, usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import authRoutes from '@/actions/App/Http/Controllers/AuthController';
import { history as historyRoute } from '@/routes';
import ciphersRoutes from '@/routes/ciphers';
import type { Auth } from '@/types/auth';

type SharedPageProps = {
    auth: Auth;
    name: string;
};

type EnigmaShellProps = {
    active: 'break' | 'catalog' | 'decrypt' | 'encrypt' | 'history';
    children: ReactNode;
    title: string;
};

const navigation = [
    {
        key: 'catalog' as const,
        label: 'Catálogo',
        icon: 'grid_view',
        url: ciphersRoutes.catalog.url(),
    },
    {
        key: 'encrypt' as const,
        label: 'Cifrar',
        icon: 'enhanced_encryption',
        url: ciphersRoutes.encrypt.url(),
    },
    {
        key: 'decrypt' as const,
        label: 'Descifrar',
        icon: 'no_encryption',
        url: ciphersRoutes.decrypt.url(),
    },
    {
        key: 'break' as const,
        label: 'Romper',
        icon: 'query_stats',
        url: ciphersRoutes.break.url(),
    },
];

export default function EnigmaShell({
    active,
    children,
    title,
}: EnigmaShellProps) {
    const { auth } = usePage<SharedPageProps>().props;
    const user = auth.user;

    return (
        <div className="min-h-screen bg-background text-on-surface antialiased">
            <aside className="fixed inset-y-0 left-0 z-50 hidden w-64 flex-col border-r border-outline-variant/30 bg-surface-container py-6 lg:flex">
                <div className="mb-8 flex items-center gap-3 px-6">
                    <span className="material-symbols-outlined text-3xl text-tertiary">
                        memory
                    </span>
                    <span className="font-headline text-xl font-black tracking-tighter">
                        ENIGMA_LABS
                    </span>
                </div>

                <div className="mx-4 mb-8 border border-outline-variant/30 bg-surface-container-low p-3">
                    <div className="flex items-center gap-3">
                        <div className="flex h-10 w-10 items-center justify-center border border-outline-variant/50 bg-primary-container text-tertiary">
                            <span className="material-symbols-outlined">
                                terminal
                            </span>
                        </div>
                        <div className="min-w-0">
                            <div className="truncate font-code text-xs font-semibold text-on-surface">
                                {user?.name ?? 'OPERADOR_01'}
                            </div>
                            <div className="font-code text-[10px] text-on-surface-variant">
                                Nivel de acceso 3
                            </div>
                        </div>
                    </div>
                </div>

                <div className="px-4">
                    <Link
                        className="flex w-full items-center justify-center gap-2 rounded-sm border border-transparent bg-tertiary px-4 py-2 font-code text-xs font-semibold tracking-wider text-primary-container uppercase transition-colors hover:bg-[#a99985]"
                        href={ciphersRoutes.encrypt()}
                    >
                        <span className="material-symbols-outlined text-base">
                            add
                        </span>
                        Nueva sesión
                    </Link>
                </div>

                <nav className="mt-8 flex-1 space-y-1 px-3">
                    <div className="mb-2 px-3 font-code text-[10px] tracking-[0.2em] text-on-surface-variant/50 uppercase">
                        Módulos
                    </div>
                    {navigation.map((item) => (
                        <Link
                            className={`flex items-center gap-3 rounded-sm px-3 py-3 font-code text-xs tracking-wide uppercase transition-all ${active === item.key ? 'border-l-2 border-tertiary bg-secondary-container text-on-surface' : 'text-on-surface-variant hover:bg-surface-container-highest hover:text-on-surface'}`}
                            href={item.url}
                            key={item.key}
                        >
                            <span className="material-symbols-outlined text-[19px]">
                                {item.icon}
                            </span>
                            {item.label}
                        </Link>
                    ))}

                    <Link
                        className={`flex items-center gap-3 rounded-sm px-3 py-3 font-code text-xs tracking-wide uppercase transition-all ${active === 'history' ? 'border-l-2 border-tertiary bg-secondary-container text-on-surface' : 'text-on-surface-variant hover:bg-surface-container-highest hover:text-on-surface'}`}
                        href={historyRoute()}
                    >
                        <span className="material-symbols-outlined text-[19px]">
                            list_alt
                        </span>
                        Registros
                    </Link>
                </nav>

                <div className="mt-auto border-t border-outline-variant/30 px-4 pt-4">
                    <Link
                        className="flex items-center gap-3 px-3 py-2 font-code text-xs text-on-surface-variant transition-colors hover:text-tertiary"
                        href={historyRoute()}
                    >
                        <span className="material-symbols-outlined text-lg">
                            menu_book
                        </span>
                        Documentación
                    </Link>
                    <Link
                        className="flex items-center gap-3 px-3 py-2 font-code text-xs text-on-surface-variant transition-colors hover:text-tertiary"
                        href={historyRoute()}
                    >
                        <span className="material-symbols-outlined text-lg">
                            settings_ethernet
                        </span>
                        Estado del sistema
                    </Link>
                    <Link
                        as="button"
                        className="mt-3 flex w-full items-center gap-3 border-t border-outline-variant/30 px-3 pt-4 text-left font-code text-xs text-on-surface-variant transition-colors hover:text-error"
                        href={authRoutes.destroy()}
                        method="post"
                    >
                        <span className="material-symbols-outlined text-lg">
                            logout
                        </span>
                        Desconectar
                    </Link>
                </div>
            </aside>

            <header className="fixed inset-x-0 top-0 z-40 flex h-16 items-center justify-between border-b border-outline-variant/30 bg-surface px-4 lg:hidden">
                <div className="flex items-center gap-2">
                    <span className="material-symbols-outlined text-tertiary">
                        terminal
                    </span>
                    <span className="font-headline text-lg font-bold tracking-tighter">
                        ENIGMA_LABS
                    </span>
                </div>
                <Link
                    className="text-on-surface-variant hover:text-tertiary"
                    href={authRoutes.destroy()}
                    method="post"
                    as="button"
                >
                    <span className="material-symbols-outlined">logout</span>
                </Link>
            </header>

            <main className="min-h-screen pt-16 lg:ml-64 lg:pt-0">
                <div
                    className="mx-auto min-h-screen w-full max-w-[1200px] px-4 py-8 sm:px-6 lg:px-6 lg:py-12"
                    data-page-title={title}
                >
                    {children}
                </div>
            </main>

            <nav className="fixed inset-x-0 bottom-0 z-50 grid grid-cols-5 border-t border-outline-variant/30 bg-surface lg:hidden">
                {navigation.map((item) => (
                    <Link
                        className={`flex flex-col items-center gap-1 px-1 py-3 font-code text-[9px] uppercase ${active === item.key ? 'bg-secondary-container text-tertiary' : 'text-on-surface-variant'}`}
                        href={item.url}
                        key={item.key}
                    >
                        <span className="material-symbols-outlined text-lg">
                            {item.icon}
                        </span>
                        {item.label}
                    </Link>
                ))}
                <Link
                    className={`flex flex-col items-center gap-1 px-1 py-3 font-code text-[9px] uppercase ${active === 'history' ? 'bg-secondary-container text-tertiary' : 'text-on-surface-variant'}`}
                    href={historyRoute()}
                >
                    <span className="material-symbols-outlined text-lg">
                        list_alt
                    </span>
                    Registros
                </Link>
            </nav>
        </div>
    );
}
