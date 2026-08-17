import { Head, useHttp } from '@inertiajs/react';
import { useState } from 'react';
import apiCiphers from '@/actions/App/Http/Api/CiphersController';
import EnigmaShell from '@/components/enigma-shell';
import type { BreakForm, BreakResponse, CipherMetadata } from '@/types/ciphers';

type BreakProps = {
    ciphers: CipherMetadata[];
};

const bars = [
    18, 32, 24, 61, 42, 78, 36, 54, 68, 26, 44, 31, 58, 72, 22, 37, 29, 48, 33,
    17,
];

export default function Break({ ciphers }: BreakProps) {
    const [algorithm, setAlgorithm] = useState('caesar');
    const [text, setText] = useState('');
    const [result, setResult] = useState<BreakResponse | null>(null);
    const request = useHttp<BreakForm, BreakResponse>({ text: '' });
    const selectedCipher = ciphers.find((cipher) => cipher.id === algorithm);

    function submit(event: React.FormEvent<HTMLFormElement>): void {
        event.preventDefault();
        setResult(null);
        request.post(
            apiCiphers.breakCipher['/api/ciphers/{algorithm}/break'](algorithm)
                .url,
            {
                onSuccess: (response) => setResult(response),
            },
        );
    }

    return (
        <EnigmaShell active="break" title="Matriz de criptoanálisis">
            <Head title="Romper / Criptoanálisis" />
            <header className="mb-10 flex flex-col gap-4 border-b border-outline-variant/30 pb-8 md:flex-row md:items-end md:justify-between">
                <div>
                    <div className="mb-2 font-code text-[10px] tracking-[0.2em] text-tertiary uppercase">
                        ENIGMA / SUPERFICIE DE ATAQUE
                    </div>
                    <h1 className="font-headline text-3xl font-bold tracking-tight sm:text-4xl">
                        Matriz de criptoanálisis
                    </h1>
                    <p className="mt-3 max-w-3xl font-code text-sm leading-relaxed text-on-surface-variant">
                        Despliega la rutina de ruptura documentada contra un
                        texto cifrado desconocido. Los resultados son
                        estimaciones, no puntuaciones de certeza.
                    </p>
                </div>
                <span className="hidden items-center gap-2 border border-outline-variant/30 bg-surface-container-high px-3 py-2 font-code text-[10px] tracking-wider text-primary uppercase md:flex">
                    <span className="h-2 w-2 bg-tertiary" />
                    SISTEMA_LISTO
                </span>
            </header>

            <form
                className="grid grid-cols-1 gap-6 lg:grid-cols-12"
                onSubmit={submit}
            >
                <div className="space-y-6 lg:col-span-5">
                    <section className="flex min-h-[380px] flex-col border border-outline-variant/30 bg-surface-container-low">
                        <div className="flex items-center justify-between border-b border-outline-variant/30 bg-surface-container px-4 py-4">
                            <div className="flex items-center font-code text-xs tracking-wider uppercase">
                                <span className="material-symbols-outlined mr-2 text-base">
                                    code_blocks
                                </span>
                                Texto cifrado objetivo
                            </div>
                            <span className="material-symbols-outlined text-on-surface-variant">
                                file_upload
                            </span>
                        </div>
                        <div className="flex flex-1 flex-col p-4">
                            <textarea
                                className="min-h-[260px] flex-1 resize-none border border-outline-variant/30 bg-[#1a1919] p-4 font-code text-sm text-on-surface outline-none placeholder:text-on-surface-variant/40 focus:border-tertiary"
                                onChange={(event) => {
                                    setText(event.target.value);
                                    request.setData('text', event.target.value);
                                }}
                                placeholder="> Introduce el texto cifrado para analizar..."
                                value={text}
                            />
                            <div className="mt-4 flex justify-between font-code text-[10px] tracking-wider text-on-surface-variant uppercase">
                                <span>
                                    Longitud:{' '}
                                    <strong className="text-on-surface">
                                        {text.length} caracteres
                                    </strong>
                                </span>
                                <span>
                                    Formato:{' '}
                                    <strong className="text-on-surface">
                                        ASCII / UTF-8
                                    </strong>
                                </span>
                            </div>
                        </div>
                    </section>

                    <section className="border border-outline-variant/30 bg-surface-container-low">
                        <div className="border-b border-outline-variant/30 bg-surface-container px-4 py-4 font-code text-xs tracking-wider uppercase">
                            <span className="material-symbols-outlined mr-2 align-middle text-base">
                                tune
                            </span>
                            Configuración del vector de ataque
                        </div>
                        <div className="space-y-5 p-6">
                            <label className="block">
                                <span className="mb-2 block font-code text-[11px] tracking-widest text-on-surface-variant uppercase">
                                    Metodología
                                </span>
                                <select
                                    className="w-full border border-tertiary bg-surface-container-highest px-3 py-3 font-code text-xs text-tertiary outline-none"
                                    onChange={(event) => {
                                        setAlgorithm(event.target.value);
                                        setResult(null);
                                    }}
                                    value={algorithm}
                                >
                                    {ciphers.map((cipher) => (
                                        <option
                                            key={cipher.id}
                                            value={cipher.id}
                                        >
                                            {cipher.name}
                                        </option>
                                    ))}
                                </select>
                            </label>
                            <div className="border-t border-outline-variant/30 pt-5">
                                <div className="font-code text-[10px] tracking-widest text-on-surface-variant uppercase">
                                    Modelo activo
                                </div>
                                <div className="mt-2 font-code text-sm text-tertiary">
                                    {selectedCipher?.name}
                                </div>
                                <div className="mt-2 font-code text-[11px] leading-relaxed text-on-surface-variant">
                                    {selectedCipher?.description}
                                </div>
                            </div>
                            <button
                                className="flex w-full items-center justify-center gap-2 bg-tertiary px-4 py-3 font-code text-xs font-bold tracking-widest text-primary-container uppercase transition-colors hover:bg-[#a99985] disabled:cursor-wait disabled:opacity-50"
                                disabled={request.processing || !text.trim()}
                                type="submit"
                            >
                                <span className="material-symbols-outlined text-lg">
                                    play_arrow
                                </span>
                                {request.processing
                                    ? 'Analizando...'
                                    : 'Iniciar secuencia'}
                            </button>
                        </div>
                    </section>
                </div>

                <div className="space-y-6 lg:col-span-7">
                    <section className="grid grid-cols-3 gap-3">
                        {[
                            [
                                'ESTADO',
                                request.processing
                                    ? 'EJECUTANDO'
                                    : result
                                      ? 'COMPLETADO'
                                      : 'EN ESPERA',
                                request.processing
                                    ? 'text-tertiary'
                                    : 'text-secondary',
                            ],
                            [
                                'CÁLCULOS',
                                result ? 'EXPLORADO' : '0 / s',
                                'text-on-surface',
                            ],
                            [
                                'IDIOMA',
                                result?.idioma ?? '--',
                                'text-on-surface',
                            ],
                        ].map(([label, value, color]) => (
                            <div
                                className="border border-outline-variant/30 bg-surface-container-low p-4"
                                key={label}
                            >
                                <div className="mb-2 font-code text-[10px] tracking-wider text-on-surface-variant uppercase">
                                    {label}
                                </div>
                                <div className={`font-code text-xs ${color}`}>
                                    <span className="mr-2 inline-block h-1.5 w-1.5 bg-current" />
                                    {value}
                                </div>
                            </div>
                        ))}
                    </section>

                    <section className="relative h-64 overflow-hidden border border-outline-variant/30 bg-surface-container-low">
                        <div className="flex items-center border-b border-outline-variant/30 bg-surface-container px-4 py-4 font-code text-xs tracking-wider uppercase">
                            <span className="material-symbols-outlined mr-2 text-base">
                                monitoring
                            </span>
                            Distribución de frecuencias
                        </div>
                        <div className="flex h-[calc(100%-53px)] items-end gap-1 bg-[#1a1919] p-5 opacity-80">
                            {bars.map((height, index) => (
                                <span
                                    className={`flex-1 ${index === 5 || index === 8 ? 'bg-tertiary' : 'bg-outline-variant/70'}`}
                                    key={index}
                                    style={{ height: `${height}%` }}
                                />
                            ))}
                        </div>
                        {!result && (
                            <div className="absolute inset-x-0 top-14 bottom-0 flex items-center justify-center">
                                <span className="border border-outline-variant/30 bg-surface-container-low px-4 py-2 font-code text-xs tracking-widest text-outline uppercase">
                                    Esperando datos
                                </span>
                            </div>
                        )}
                    </section>

                    <section className="border border-outline-variant/30 bg-surface-container-low">
                        <div className="flex items-center justify-between border-b border-outline-variant/30 bg-surface-container px-4 py-4">
                            <div className="font-code text-xs tracking-wider uppercase">
                                <span className="material-symbols-outlined mr-2 align-middle text-base">
                                    view_list
                                </span>
                                Salida de candidato
                            </div>
                            <span className="font-code text-[10px] text-on-surface-variant uppercase">
                                Mejor coincidencia
                            </span>
                        </div>
                        <div className="bg-[#1a1919] p-5">
                            {result ? (
                                <div className="space-y-4">
                                    <div className="grid gap-3 sm:grid-cols-3">
                                        <div>
                                            <div className="font-code text-[10px] text-on-surface-variant uppercase">
                                                Clave recuperada
                                            </div>
                                            <div className="mt-1 font-code text-sm text-tertiary">
                                                {result.recoveredKey || '--'}
                                            </div>
                                        </div>
                                        <div>
                                            <div className="font-code text-[10px] text-on-surface-variant uppercase">
                                                Puntuación
                                            </div>
                                            <div className="mt-1 font-code text-sm text-primary">
                                                {result.score.toFixed(3)}
                                            </div>
                                        </div>
                                        <div>
                                            <div className="font-code text-[10px] text-on-surface-variant uppercase">
                                                Idioma
                                            </div>
                                            <div className="mt-1 font-code text-sm text-secondary">
                                                {result.idioma}
                                            </div>
                                        </div>
                                    </div>
                                    <div className="border border-tertiary/40 bg-surface-container-low p-4 font-code text-sm leading-relaxed text-tertiary">
                                        {result.plainText}
                                    </div>
                                </div>
                            ) : (
                                <div className="grid min-h-32 place-items-center font-code text-xs tracking-widest text-outline uppercase">
                                    Aún no hay candidatos
                                </div>
                            )}
                        </div>
                    </section>

                    {request.hasErrors && (
                        <div className="border border-error/40 bg-error-container/20 p-4 font-code text-xs text-error">
                            {Object.values(request.errors).flat().join(' ')}
                        </div>
                    )}
                </div>
            </form>
        </EnigmaShell>
    );
}
