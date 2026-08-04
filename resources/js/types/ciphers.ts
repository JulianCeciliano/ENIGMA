export type CipherParameter = {
    name: string;
    label: string;
    type: 'text' | 'number' | 'select';
    required: boolean;
    defaultValue: string | number | null;
    placeholder: string | null;
    helpText: string | null;
    min: number | null;
    max: number | null;
    options: Array<{ label: string; value: string }> | null;
};

export type CipherMetadata = {
    id: string;
    name: string;
    description: string;
    formula: string;
    parameters: CipherParameter[];
};

export type CipherParameters = Record<string, string | number>;

export type TransformForm = {
    algorithm: string;
    operation: 'encrypt' | 'decrypt';
    text: string;
    parameters: CipherParameters;
    includeSteps: boolean;
};

export type CipherStep = {
    title: string;
    description: string;
    headers: string[];
    rows: Array<Array<string | number>>;
};

export type TransformResponse = {
    algorithm: string;
    algorithmName: string;
    operation: 'encrypt' | 'decrypt';
    result: string;
    keyUsed: string;
    explanation: string;
    steps: CipherStep[];
};

export type BreakForm = {
    text: string;
};

export type BreakResponse = {
    algorithm: string;
    algorithmName: string;
    plainText: string;
    recoveredKey: string;
    score: number;
    idioma: 'Espanol' | 'Ingles';
};

export type HistoryOperation = {
    id: number;
    timestamp: string | null;
    operation: string;
    algorithm: string;
    status: string;
    payloadPreview: string;
    inputHash: string;
    outputHash: string | null;
    inputText: string;
    outputText: string | null;
    parameters: CipherParameters;
    recoveredKey: string | null;
    score: number | null;
    idioma: string | null;
    durationMs: number | null;
    errorMessage: string | null;
};

export type Paginated<T> = {
    current_page: number;
    data: T[];
    from: number | null;
    last_page: number;
    per_page: number;
    to: number | null;
    total: number;
};
