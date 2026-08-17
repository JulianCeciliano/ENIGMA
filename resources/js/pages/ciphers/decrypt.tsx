import { Head } from '@inertiajs/react';
import CipherWorkbench from '@/components/cipher-workbench';
import EnigmaShell from '@/components/enigma-shell';
import type { CipherMetadata } from '@/types/ciphers';

type DecryptProps = {
    ciphers: CipherMetadata[];
};

export default function Decrypt({ ciphers }: DecryptProps) {
    return (
        <EnigmaShell active="decrypt" title="Protocolo de descifrado">
            <Head title="Descifrar" />
            <header className="mb-10 border-b border-outline-variant/30 pb-8">
                <div className="mb-2 flex items-center gap-2 font-code text-[10px] tracking-widest text-on-surface-variant uppercase">
                    <span>Modo de recuperación</span>
                    <span className="h-1 w-1 bg-tertiary" />
                    <span className="text-tertiary">Parámetros conocidos</span>
                </div>
                <h1 className="font-headline text-3xl font-bold tracking-tight sm:text-4xl">
                    Protocolo de descifrado
                </h1>
                <p className="mt-3 max-w-3xl font-code text-sm leading-relaxed text-on-surface-variant">
                    Aplica la función inversa de un cifrado clásico documentado.
                    Las claves se usan solo para esta operación y nunca se
                    tratan como material de seguridad real.
                </p>
            </header>
            <CipherWorkbench ciphers={ciphers} mode="decrypt" />
        </EnigmaShell>
    );
}
