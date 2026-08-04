import { Head } from '@inertiajs/react';
import CipherWorkbench from '@/components/cipher-workbench';
import EnigmaShell from '@/components/enigma-shell';
import type { CipherMetadata } from '@/types/ciphers';

type DecryptProps = {
    ciphers: CipherMetadata[];
};

export default function Decrypt({ ciphers }: DecryptProps) {
    return (
        <EnigmaShell active="decrypt" title="Decryption Protocol">
            <Head title="Decrypt" />
            <header className="mb-10 border-b border-outline-variant/30 pb-8">
                <div className="mb-2 flex items-center gap-2 font-code text-[10px] tracking-widest text-on-surface-variant uppercase">
                    <span>Recovery Mode</span>
                    <span className="h-1 w-1 bg-tertiary" />
                    <span className="text-tertiary">Known Parameters</span>
                </div>
                <h1 className="font-headline text-3xl font-bold tracking-tight sm:text-4xl">
                    Decryption Protocol
                </h1>
                <p className="mt-3 max-w-3xl font-code text-sm leading-relaxed text-on-surface-variant">
                    Apply the inverse function for a documented classical
                    cipher. Keys are used only for this operation and are never
                    treated as real security material.
                </p>
            </header>
            <CipherWorkbench ciphers={ciphers} mode="decrypt" />
        </EnigmaShell>
    );
}
