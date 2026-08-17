import { useHttp } from '@inertiajs/react';
import { useState } from 'react';
import apiCiphers from '@/actions/App/Http/Api/CiphersController';
import type {
    CipherMetadata,
    CipherParameters,
    TransformForm,
    TransformResponse,
} from '@/types/ciphers';

type CipherWorkbenchProps = {
    ciphers: CipherMetadata[];
    mode: 'decrypt' | 'encrypt';
};

function defaultParameters(cipher: CipherMetadata): CipherParameters {
    return Object.fromEntries(
        cipher.parameters
            .filter((parameter) => parameter.defaultValue !== null)
            .map((parameter) => [
                parameter.name,
                parameter.defaultValue as string | number,
            ]),
    );
}

export default function CipherWorkbench({
    ciphers,
    mode,
}: CipherWorkbenchProps) {
    const initialCipher =
        ciphers.find((cipher) => cipher.id === 'caesar') ?? ciphers[0];
    const [algorithm, setAlgorithm] = useState(initialCipher?.id ?? 'atbash');
    const [result, setResult] = useState<TransformResponse | null>(null);
    const [requestError, setRequestError] = useState<string | null>(null);
    const selectedCipher =
        ciphers.find((cipher) => cipher.id === algorithm) ?? initialCipher;
    const [form, setForm] = useState<TransformForm>({
        algorithm: initialCipher?.id ?? 'atbash',
        operation: mode,
        text: '',
        parameters: initialCipher ? defaultParameters(initialCipher) : {},
        includeSteps: true,
    });
    const request = useHttp<TransformForm, TransformResponse>(form);

    function updateAlgorithm(nextAlgorithm: string): void {
        const nextCipher = ciphers.find(
            (cipher) => cipher.id === nextAlgorithm,
        );
        const nextParameters = nextCipher ? defaultParameters(nextCipher) : {};

        setAlgorithm(nextAlgorithm);
        setResult(null);
        setRequestError(null);
        setForm((current) => ({
            ...current,
            algorithm: nextAlgorithm,
            parameters: nextParameters,
        }));
        request.setData({
            algorithm: nextAlgorithm,
            parameters: nextParameters,
        });
    }

    function updateParameter(name: string, value: string): void {
        const nextParameters = { ...form.parameters, [name]: value };

        setForm((current) => ({ ...current, parameters: nextParameters }));
        request.setData({ parameters: nextParameters });
    }

    function updateText(value: string): void {
        setForm((current) => ({ ...current, text: value }));
        setRequestError(null);
        request.setData('text', value);
    }

    function submit(event: React.FormEvent<HTMLFormElement>): void {
        event.preventDefault();
        setResult(null);
        setRequestError(null);

        // Keep the HTTP hook aligned with the controlled form before submitting.
        request.setData(form);

        request
            .post(apiCiphers.transform['/api/ciphers/transform'].url(), {
                onSuccess: (response) => setResult(response),
            })
            .catch(() => {
                setRequestError(
                    'No se pudo procesar la operación. Verifica la sesión y los parámetros.',
                );
            });
    }

    async function copyResult(): Promise<void> {
        if (result?.result) {
            await navigator.clipboard.writeText(result.result);
        }
    }

    function downloadResult(): void {
        if (!result?.result) {
            return;
        }

        const blob = new Blob([result.result], {
            type: 'text/plain;charset=utf-8',
        });
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = `${mode}-${algorithm}.txt`;
        link.click();
        URL.revokeObjectURL(url);
    }

    return (
        <form
            className="grid grid-cols-1 gap-6 xl:grid-cols-12"
            onSubmit={submit}
        >
            <div className="flex flex-col gap-6 xl:col-span-8">
                <section className="flex min-h-[280px] flex-col border border-outline-variant/30 bg-surface-container-low">
                    <div className="flex items-center justify-between border-b border-outline-variant/30 bg-surface-container px-6 py-3">
                        <h2 className="flex items-center gap-2 font-code text-xs font-semibold tracking-wider text-on-surface uppercase">
                            <span className="material-symbols-outlined text-base text-tertiary">
                                input
                            </span>
                            {mode === 'encrypt'
                                ? 'Entrada de texto plano'
                                : 'Entrada de texto cifrado'}
                        </h2>
                        <span className="font-code text-[10px] text-on-surface-variant">
                            CARACTERES: {form.text.length}
                        </span>
                    </div>
                    <textarea
                        className="min-h-[240px] flex-1 resize-none border-0 bg-[#1a1919] p-6 font-code text-sm text-on-surface outline-none placeholder:text-on-surface-variant/40 focus:ring-1 focus:ring-tertiary"
                        onChange={(event) => updateText(event.target.value)}
                        placeholder={
                            mode === 'encrypt'
                                ? 'Introduce el texto plano para cifrar...'
                                : 'Pega el texto cifrado para recuperar...'
                        }
                        value={form.text}
                    />
                </section>

                <section className="border border-outline-variant/30 bg-surface-container-low p-6">
                    <div className="grid gap-6 md:grid-cols-2">
                        <label className="flex flex-col gap-2">
                            <span className="font-code text-[11px] tracking-widest text-on-surface-variant uppercase">
                                Algoritmo
                            </span>
                            <select
                                className="border border-outline-variant/30 bg-[#1a1919] px-4 py-3 font-code text-sm text-on-surface outline-none focus:border-tertiary"
                                onChange={(event) =>
                                    updateAlgorithm(event.target.value)
                                }
                                value={algorithm}
                            >
                                {ciphers.map((cipher) => (
                                    <option key={cipher.id} value={cipher.id}>
                                        {cipher.name}
                                    </option>
                                ))}
                            </select>
                        </label>
                        <div className="flex items-end">
                            <label className="flex items-center gap-3 font-code text-xs text-on-surface-variant">
                                <input
                                    checked={form.includeSteps}
                                    className="h-4 w-4 accent-tertiary"
                                    onChange={(event) => {
                                        const includeSteps =
                                            event.target.checked;
                                        setForm((current) => ({
                                            ...current,
                                            includeSteps,
                                        }));
                                        request.setData(
                                            'includeSteps',
                                            includeSteps,
                                        );
                                    }}
                                    type="checkbox"
                                />
                                Incluir pasos educativos
                            </label>
                        </div>
                    </div>

                    <div className="mt-6 grid gap-4 md:grid-cols-2">
                        {selectedCipher?.parameters.map((parameter) => (
                            <label
                                className="flex flex-col gap-2"
                                key={parameter.name}
                            >
                                <span className="font-code text-[11px] tracking-widest text-on-surface-variant uppercase">
                                    {parameter.label}
                                </span>
                                <input
                                    className="border border-outline-variant/30 bg-[#1a1919] px-4 py-3 font-code text-sm text-on-surface outline-none focus:border-tertiary"
                                    max={parameter.max ?? undefined}
                                    min={parameter.min ?? undefined}
                                    onChange={(event) =>
                                        updateParameter(
                                            parameter.name,
                                            event.target.value,
                                        )
                                    }
                                    placeholder={parameter.placeholder ?? ''}
                                    type={parameter.type}
                                    value={
                                        form.parameters[parameter.name] ?? ''
                                    }
                                />
                                <span className="font-code text-[10px] leading-relaxed text-on-surface-variant/70">
                                    {parameter.helpText}
                                </span>
                            </label>
                        ))}
                    </div>

                    <div className="mt-6 flex justify-end border-t border-outline-variant/30 pt-6">
                        <button
                            className="flex items-center gap-2 bg-tertiary px-8 py-3 font-code text-xs font-bold tracking-widest text-primary-container uppercase transition-colors hover:bg-[#a99985] disabled:cursor-wait disabled:opacity-50"
                            disabled={request.processing || !form.text.trim()}
                            type="submit"
                        >
                            <span className="material-symbols-outlined text-lg">
                                {mode === 'encrypt'
                                    ? 'enhanced_encryption'
                                    : 'lock_open'}
                            </span>
                            {request.processing
                                ? 'Procesando...'
                                : mode === 'encrypt'
                                  ? 'Ejecutar cifrado'
                                  : 'Ejecutar descifrado'}
                        </button>
                    </div>
                </section>

                <section className="relative flex min-h-[260px] flex-col border border-tertiary/50 bg-surface-container-low">
                    <div className="flex items-center justify-between border-b border-outline-variant/30 bg-surface-container-lowest px-6 py-3">
                        <h2 className="flex items-center gap-2 font-code text-xs font-semibold tracking-wider text-tertiary uppercase">
                            <span className="material-symbols-outlined text-base">
                                output
                            </span>
                            {mode === 'encrypt'
                                ? 'Salida de texto cifrado'
                                : 'Texto recuperado'}
                        </h2>
                        <div className="flex gap-3">
                            <button
                                className="text-on-surface-variant transition-colors hover:text-tertiary disabled:opacity-30"
                                disabled={!result}
                                onClick={copyResult}
                                title="Copiar"
                                type="button"
                            >
                                <span className="material-symbols-outlined text-lg">
                                    content_copy
                                </span>
                            </button>
                            <button
                                className="text-on-surface-variant transition-colors hover:text-tertiary disabled:opacity-30"
                                disabled={!result}
                                onClick={downloadResult}
                                title="Descargar"
                                type="button"
                            >
                                <span className="material-symbols-outlined text-lg">
                                    download
                                </span>
                            </button>
                        </div>
                    </div>
                    <textarea
                        className="min-h-[200px] flex-1 resize-none border-0 bg-[#1a1919] p-6 font-code text-sm text-tertiary outline-none"
                        readOnly
                        value={result?.result ?? ''}
                        placeholder="Esperando ejecución..."
                    />
                </section>
            </div>

            <aside className="flex flex-col gap-6 xl:col-span-4">
                <section className="flex-1 border border-outline-variant/30 bg-surface-container-low">
                    <div className="border-b border-outline-variant/30 bg-surface-container px-6 py-4">
                        <h2 className="flex items-center gap-2 font-code text-xs font-semibold tracking-wider uppercase">
                            <span className="material-symbols-outlined text-base text-secondary">
                                science
                            </span>
                            Lógica operativa
                        </h2>
                    </div>
                    <div className="flex flex-col gap-6 p-6">
                        <div className="border border-outline-variant/30 bg-[#1a1919] p-4 text-center">
                            <div className="flex items-center justify-center gap-3 font-code text-sm">
                                <span className="border border-outline-variant/30 bg-surface-container px-3 py-2">
                                    A
                                </span>
                                <span className="material-symbols-outlined text-tertiary">
                                    arrow_forward
                                </span>
                                <span className="border border-tertiary/50 bg-tertiary/10 px-3 py-2 text-tertiary">
                                    {selectedCipher?.name.slice(0, 1) ?? 'X'}
                                </span>
                            </div>
                            <div className="mt-3 font-code text-[10px] text-on-surface-variant">
                                {selectedCipher?.formula}
                            </div>
                        </div>

                        <div>
                            <h3 className="mb-4 border-b border-outline-variant/30 pb-2 font-code text-[11px] tracking-widest uppercase">
                                Secuencia del algoritmo
                            </h3>
                            <ol className="space-y-4 border-l border-outline-variant/30 pl-5">
                                <li className="relative font-code text-xs text-on-surface-variant before:absolute before:top-0.5 before:-left-[25px] before:h-3 before:w-3 before:border before:border-tertiary before:bg-[#1a1919]">
                                    Selecciona un método clásico documentado.
                                </li>
                                <li className="relative font-code text-xs text-on-surface-variant before:absolute before:top-0.5 before:-left-[25px] before:h-3 before:w-3 before:border before:border-outline-variant before:bg-[#1a1919]">
                                    Aplica las reglas de letras ASCII y conserva
                                    el formato.
                                </li>
                                <li className="relative font-code text-xs text-on-surface-variant before:absolute before:top-0.5 before:-left-[25px] before:h-3 before:w-3 before:border before:border-outline-variant before:bg-[#1a1919]">
                                    Devuelve el resultado, metadatos de la clave
                                    y pasos opcionales.
                                </li>
                            </ol>
                        </div>

                        <div className="border border-error/30 bg-error-container/10 p-3">
                            <div className="flex gap-3">
                                <span className="material-symbols-outlined text-lg text-error">
                                    warning
                                </span>
                                <div>
                                    <div className="font-code text-[10px] tracking-widest text-error uppercase">
                                        Advertencia educativa
                                    </div>
                                    <p className="mt-1 font-code text-[11px] leading-relaxed text-on-surface-variant">
                                        Estos cifrados históricos no deben
                                        utilizarse para proteger secretos
                                        reales.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                {(request.hasErrors || requestError) && (
                    <div className="border border-error/40 bg-error-container/20 p-4 font-code text-xs text-error">
                        {requestError ??
                            Object.values(request.errors).flat().join(' ')}
                    </div>
                )}
            </aside>
        </form>
    );
}
