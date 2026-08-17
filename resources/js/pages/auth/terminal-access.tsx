import { Form, Head, Link } from '@inertiajs/react';
import authRoutes from '@/actions/App/Http/Controllers/AuthController';
import { register } from '@/routes';

export default function TerminalAccess() {
    return (
        <>
            <Head title="Terminal Access" />
            <main className="grid-blueprint flex min-h-screen items-center justify-center bg-background px-4 py-12 text-on-surface">
                <div className="w-full max-w-md">
                    <header className="mb-8 text-center">
                        <div className="mb-2 font-headline text-3xl font-black tracking-tighter">
                            ENIGMA_LABS
                        </div>
                        <div className="flex items-center justify-center gap-2 font-code text-xs tracking-widest text-on-surface-variant uppercase">
                            <span className="h-2 w-2 bg-tertiary" />
                            Terminal Access
                            <span className="h-2 w-2 bg-tertiary" />
                        </div>
                    </header>

                    <section className="relative border border-outline-variant/40 bg-primary-container p-6 shadow-2xl sm:p-8">
                        <span className="absolute top-0 left-0 h-3 w-3 border-t border-l border-tertiary" />
                        <span className="absolute top-0 right-0 h-3 w-3 border-t border-r border-tertiary" />
                        <span className="absolute bottom-0 left-0 h-3 w-3 border-b border-l border-tertiary" />
                        <span className="absolute right-0 bottom-0 h-3 w-3 border-r border-b border-tertiary" />

                        <Form {...authRoutes.store.form()} resetOnError>
                            {({ errors, processing }) => (
                                <div className="space-y-5">
                                    <div className="border-b border-outline-variant/30 pb-4">
                                        <div className="font-code text-[10px] tracking-[0.2em] text-tertiary uppercase">
                                            Secure operator session
                                        </div>
                                        <p className="mt-2 font-code text-xs leading-relaxed text-on-surface-variant">
                                            Authenticate to access the cipher
                                            laboratory and operation history.
                                        </p>
                                    </div>

                                    <label className="block space-y-2">
                                        <span className="font-code text-[11px] tracking-widest text-on-surface-variant uppercase">
                                            Operator ID / Email
                                        </span>
                                        <div className="flex items-center border border-outline-variant/40 bg-[#1a1919] focus-within:border-tertiary">
                                            <span className="material-symbols-outlined pl-3 text-on-surface-variant">
                                                person
                                            </span>
                                            <input
                                                autoComplete="email"
                                                className="w-full border-0 bg-transparent px-3 py-3 font-code text-sm text-on-surface outline-none placeholder:text-on-surface-variant/40 focus:ring-0"
                                                name="email"
                                                placeholder="operator@enigma.labs"
                                                type="email"
                                            />
                                        </div>
                                        {errors.email && (
                                            <span className="font-code text-xs text-error">
                                                {errors.email}
                                            </span>
                                        )}
                                    </label>

                                    <label className="block space-y-2">
                                        <span className="font-code text-[11px] tracking-widest text-on-surface-variant uppercase">
                                            Access Token / Password
                                        </span>
                                        <div className="flex items-center border border-outline-variant/40 bg-[#1a1919] focus-within:border-tertiary">
                                            <span className="material-symbols-outlined pl-3 text-on-surface-variant">
                                                key
                                            </span>
                                            <input
                                                autoComplete="current-password"
                                                className="w-full border-0 bg-transparent px-3 py-3 font-code text-sm text-on-surface outline-none placeholder:text-on-surface-variant/40 focus:ring-0"
                                                name="password"
                                                placeholder="••••••••••••"
                                                type="password"
                                            />
                                        </div>
                                        {errors.password && (
                                            <span className="font-code text-xs text-error">
                                                {errors.password}
                                            </span>
                                        )}
                                    </label>

                                    <label className="flex items-center gap-2 font-code text-xs text-on-surface-variant">
                                        <input
                                            className="h-4 w-4 accent-tertiary"
                                            name="remember"
                                            type="checkbox"
                                            value="1"
                                        />
                                        Keep terminal session active
                                    </label>

                                    <button
                                        className="flex w-full items-center justify-center gap-2 bg-tertiary px-4 py-3 font-code text-xs font-bold tracking-widest text-primary-container uppercase transition-colors hover:bg-[#a99985] disabled:cursor-wait disabled:opacity-50"
                                        disabled={processing}
                                        type="submit"
                                    >
                                        <span className="material-symbols-outlined text-lg">
                                            login
                                        </span>
                                        {processing
                                            ? 'Authenticating...'
                                            : 'Access Terminal'}
                                    </button>

                                    <div className="flex items-center gap-4 pt-2">
                                        <span className="h-px flex-1 bg-outline-variant/30" />
                                        <span className="font-code text-[10px] tracking-widest text-on-surface-variant uppercase">
                                            New operator
                                        </span>
                                        <span className="h-px flex-1 bg-outline-variant/30" />
                                    </div>

                                    <Link
                                        className="flex w-full items-center justify-center gap-2 border border-outline px-4 py-3 font-code text-xs font-semibold tracking-widest text-on-surface uppercase transition-colors hover:border-tertiary hover:bg-surface-container-high"
                                        href={register()}
                                    >
                                        <span className="material-symbols-outlined text-base">
                                            how_to_reg
                                        </span>
                                        Request Access
                                    </Link>
                                </div>
                            )}
                        </Form>
                    </section>

                    <div className="mt-8 flex items-center justify-center gap-2 font-code text-[10px] tracking-widest text-on-surface-variant/60 uppercase">
                        <span className="material-symbols-outlined text-sm">
                            lock
                        </span>
                        Session protected by Laravel Passport
                    </div>
                </div>
            </main>
        </>
    );
}
