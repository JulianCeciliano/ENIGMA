import { Head } from '@inertiajs/react';
import CipherWorkbench from '@/components/cipher-workbench';
import EnigmaShell from '@/components/enigma-shell';
import type { CipherMetadata } from '@/types/ciphers';

type EncryptProps = {
    ciphers: CipherMetadata[];
};

export default function Encrypt({ ciphers }: EncryptProps) {
    return (
        <EnigmaShell active="encrypt" title="Encryption Engine">
            <Head title="Encrypt" />
            <header className="mb-10 border-b border-outline-variant/30 pb-8">
                <div className="mb-2 flex items-center gap-2 font-code text-[10px] tracking-widest text-on-surface-variant uppercase">
                    <span>Method Registry</span>
                    <span className="h-1 w-1 bg-tertiary" />
                    <span className="text-tertiary">Encryption</span>
                </div>
                <h1 className="font-headline text-3xl font-bold tracking-tight sm:text-4xl">
                    Encryption Engine
                </h1>
                <p className="mt-3 max-w-3xl font-code text-sm leading-relaxed text-on-surface-variant">
                    Execute a classical cipher against plaintext. Select the
                    method, inspect its parameters and keep every transformation
                    reproducible.
                </p>
            </header>
            <CipherWorkbench ciphers={ciphers} mode="encrypt" />
        </EnigmaShell>
    );
}
