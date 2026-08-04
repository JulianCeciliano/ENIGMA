# ENIGMA: documentación para migración a Laravel

## 1. Objetivo

Este documento describe la lógica que utiliza el proyecto ASP.NET para:

- Cifrar textos con diez cifrados clásicos.
- Descifrar textos cuando se conoce la clave o los parámetros.
- Intentar romper el cifrado cuando no se conoce la clave.
- Exponer la funcionalidad mediante una API que puede reproducirse en Laravel.

Los algoritmos son históricos y educativos. **No deben utilizarse para proteger información real.** Para secretos reales en Laravel se debe utilizar `Crypt` o una construcción moderna basada en libsodium/OpenSSL.

La implementación original se encuentra principalmente en:

```text
ENIGMA.Server/Ciphers/
ENIGMA.Server/Services/CipherService.cs
ENIGMA.Server/Controllers/CiphersController.cs
```

---

## 2. Arquitectura original

Cada algoritmo implementa la misma interfaz conceptual:

```text
id                identificador usado por la API
metadata          nombre, descripción, fórmula y parámetros del formulario
encrypt(text, parameters, includeSteps)
decrypt(text, parameters, includeSteps)
break(cipherText)
```

Las diez estrategias registradas son:

| Identificador | Clase original | Nombre visible |
|---|---|---|
| `atbash` | `AtbashCipher` | Atbash |
| `caesar` | `CaesarCipher` | César |
| `rot13` | `Rot13Cipher` | ROT13 |
| `substitution` | `MonoalphabeticSubstitutionCipher` | Sustitución monoalfabética |
| `affine` | `AffineCipher` | Afín |
| `vigenere` | `VigenereCipher` | Vigenère |
| `beaufort` | `BeaufortCipher` | Beaufort |
| `autokey` | `AutokeyCipher` | Autokey (autoclave) |
| `rail-fence` | `RailFenceCipher` | Rail Fence |
| `columnar` | `ColumnarTranspositionCipher` | Transposición Columnar |

### Distribución recomendada en Laravel

Una traducción directa puede usar esta estructura:

```text
app/
  Http/Controllers/CiphersController.php
  Http/Requests/TransformCipherRequest.php
  Http/Requests/BreakCipherRequest.php
  Services/
    CipherService.php
    Ciphers/
      ClassicCipher.php
      AtbashCipher.php
      CaesarCipher.php
      Rot13Cipher.php
      MonoalphabeticSubstitutionCipher.php
      AffineCipher.php
      VigenereCipher.php
      BeaufortCipher.php
      AutokeyCipher.php
      RailFenceCipher.php
      ColumnarTranspositionCipher.php
    Cryptanalysis/
      LanguageAnalyzer.php
      PolyalphabeticCryptanalysis.php
      CryptanalysisHelpers.php
```

La interfaz PHP equivalente puede ser:

```php
interface ClassicCipher
{
    public function id(): string;

    public function metadata(): array;

    public function encrypt(string $text, array $parameters, bool $includeSteps): array;

    public function decrypt(string $text, array $parameters, bool $includeSteps): array;

    public function break(string $cipherText): array;
}
```

`CipherService` mantiene un diccionario `id => estrategia`, valida el algoritmo y delega la operación. No contiene la matemática de cada cifrado.

---

## 3. Convenciones comunes

### 3.1 Alfabeto y posiciones

Se utiliza únicamente el alfabeto ASCII:

```text
ABCDEFGHIJKLMNOPQRSTUVWXYZ
 0 1 2 3 4 5 ...             25
```

Si `x` es una posición, la operación modular correcta debe devolver siempre un valor entre `0` y `25`:

```php
function mod(int $value, int $modulus = 26): int
{
    return (($value % $modulus) + $modulus) % $modulus;
}
```

Esto es importante porque el operador `%` de PHP puede devolver valores negativos.

### 3.2 Letras procesadas

Para sustitución, César, ROT13, Afín, Vigenère, Beaufort y Autokey:

- `A-Z` se transforma y se conserva en mayúscula.
- `a-z` se transforma y se conserva en minúscula.
- Espacios, signos, números, saltos de línea y cualquier carácter que no sea `A-Z`/`a-z` se copia sin cambios.
- En Vigenère, Beaufort y Autokey, los caracteres que no sean letras ASCII no consumen una posición de la clave.
- Las letras fuera del rango ASCII, por ejemplo `Ñ`, `Á` o `ü`, no se transforman y tampoco consumen una posición de la clave.

Helpers equivalentes:

```php
function letterIndex(string $character): ?int
{
    $code = ord($character);

    if ($code >= ord('A') && $code <= ord('Z')) {
        return $code - ord('A');
    }

    if ($code >= ord('a') && $code <= ord('z')) {
        return $code - ord('a');
    }

    return null;
}

function fromIndex(int $index, string $original): string
{
    $result = chr(ord('A') + mod($index));

    return $original === strtolower($original) ? strtolower($result) : $result;
}
```

En una implementación real conviene comprobar explícitamente si el carácter está en `a-z`; la comparación de mayúsculas anterior es solo pseudocódigo.

### 3.3 Transposiciones

Rail Fence y Transposición Columnar son diferentes de los cifrados de sustitución:

- Mueven **todos** los caracteres, incluidos espacios y signos.
- No cambian ningún carácter.
- El texto cifrado conserva la misma longitud que el texto original.
- No se añade relleno al final de la matriz.

### 3.4 Normalización de claves

Las claves de letras se recortan y se convierten a mayúsculas. Después se validan con `A-Z` ASCII.

Reglas:

| Parámetro | Regla del backend |
|---|---|
| Sustitución | Exactamente 26 letras, cada una una sola vez. |
| Vigenère | Una o más letras `A-Z`. |
| Beaufort | Una o más letras `A-Z`. |
| Autokey | Una o más letras `A-Z`. |
| Columnar | Mínimo 2 caracteres, solo `A-Z` o dígitos. |
| César | Entero obligatorio; se normaliza módulo 26. |
| Afín `a` | Entero cuyo valor módulo 26 sea coprimo con 26. |
| Afín `b` | Entero obligatorio; se normaliza módulo 26. |
| Rail Fence | Entero entre 2 y 100. |

Los valores `min` y `max` de los metadatos sirven para la interfaz. El backend de César y de `b` afín acepta también enteros fuera de esos rangos y los normaliza módulo 26.

### 3.5 Límite de los pasos educativos

`includeSteps` controla si la respuesta incluye las tablas visuales:

- Máximo 160 filas de proceso de letras.
- Máximo 80 columnas en las matrices.
- La tabla de mapeo de alfabetos siempre tiene 26 filas.

Estos límites solo afectan la explicación, no el resultado criptográfico.

---

## 4. Catálogo y parámetros

Los valores por defecto expuestos por `GET /api/ciphers` son:

| ID | Parámetros | Valores por defecto |
|---|---|---|
| `atbash` | Ninguno | - |
| `caesar` | `shift` | `3` |
| `rot13` | Ninguno | - |
| `substitution` | `key` | `QWERTYUIOPASDFGHJKLZXCVBNM` |
| `affine` | `a`, `b` | `5`, `8` |
| `vigenere` | `key` | `LEMON` |
| `beaufort` | `key` | `FORT` |
| `autokey` | `key` | `QUEEN` |
| `rail-fence` | `rails` | `3` |
| `columnar` | `key` | `ZEBRAS` |

La clave de sustitución por defecto es una permutación completa del alfabeto tipo teclado QWERTY.

---

## 5. API que debe reproducirse en Laravel

### 5.1 Obtener catálogo

```http
GET /api/ciphers
```

La respuesta es una lista de objetos con esta forma:

```json
[
  {
    "id": "vigenere",
    "name": "Vigenère",
    "description": "Aplica desplazamientos diferentes siguiendo una palabra clave repetida.",
    "formula": "E(x, k) = (x + k) mod 26",
    "parameters": [
      {
        "name": "key",
        "label": "Palabra clave",
        "type": "text",
        "required": true,
        "defaultValue": "LEMON",
        "placeholder": "Una palabra de A a Z",
        "helpText": "La clave se repite únicamente sobre las letras del texto.",
        "min": null,
        "max": null,
        "options": null
      }
    ]
  }
]
```

### 5.2 Cifrar o descifrar

```http
POST /api/ciphers/transform
Content-Type: application/json
```

El backend también tiene el prefijo alternativo `/api/cifrado`.

Request de ejemplo:

```json
{
  "algorithm": "vigenere",
  "operation": "encrypt",
  "text": "ATAQUE AL AMANECER",
  "parameters": {
    "key": "LEMON"
  },
  "includeSteps": true
}
```

Valores aceptados para `operation`:

```text
encrypt, cifrar, decrypt, descifrar
```

La comparación ignora mayúsculas y espacios alrededor.

Response:

```json
{
  "algorithm": "vigenere",
  "algorithmName": "Vigenère",
  "operation": "encrypt",
  "result": "LXFOPV EF RNHR...",
  "keyUsed": "Palabra clave: LEMON",
  "explanation": "Los espacios y signos se conservan y no avanzan la posición de la clave.",
  "steps": [
    {
      "title": "Flujo de Vigenère",
      "description": "Cada letra suma el valor de la letra correspondiente de la clave.",
      "headers": ["Posición", "Entrada", "Clave", "Salida"],
      "rows": [["1", "A", "L", "L"]]
    }
  ]
}
```

`includeSteps` vale `true` por defecto en el contrato original. Para una respuesta más pequeña se puede enviar `false`.

### 5.3 Romper un cifrado

```http
POST /api/ciphers/{algorithm}/romper
POST /api/ciphers/{algorithm}/break
Content-Type: application/json
```

También existen las rutas con prefijo `/api/cifrado`.

El cuerpo acepta cualquiera de estas propiedades:

```json
{ "text": "...texto cifrado..." }
```

o:

```json
{ "cipherText": "...texto cifrado..." }
```

Si llegan las dos, se utiliza `text`. Si `text` existe pero está vacío, no se hace fallback a `cipherText`.

Response:

```json
{
  "algorithm": "caesar",
  "algorithmName": "César",
  "plainText": "EL MENSAJE ORIGINAL",
  "recoveredKey": "7",
  "score": -2.731,
  "idioma": "Espanol"
}
```

`score` es una puntuación interna, no un porcentaje de certeza. `idioma` solo puede ser `Espanol` o `Ingles`.

### 5.4 Errores

Las validaciones devuelven HTTP `400` con una estructura equivalente a:

```json
{
  "title": "No se pudo procesar la solicitud",
  "detail": "Revisa los datos indicados y vuelve a intentarlo.",
  "status": 400,
  "errors": {
    "a": [
      "El multiplicador a debe ser coprimo con 26."
    ]
  }
}
```

Validaciones generales:

- `algorithm` obligatorio y existente.
- `operation` obligatorio en transformaciones.
- `text` obligatorio y con longitud mayor que cero.
- El algoritmo valida solo los parámetros que utiliza.

---

## 6. Algoritmos de cifrado, descifrado y break

En las fórmulas siguientes `A = 0`, `B = 1`, ..., `Z = 25`.

### 6.1 Atbash

**ID:** `atbash`  
**Clave:** ninguna  
**Fórmula:**

```text
E(x) = 25 - x
```

#### Cifrado

Se reemplaza cada letra por su posición espejo:

```text
A <-> Z
B <-> Y
C <-> X
...
```

#### Descifrado

Es exactamente la misma operación. Atbash es involutivo:

```text
E(E(x)) = x
```

#### Break

No se busca una clave. Se aplica Atbash una vez al texto cifrado y se devuelve:

```text
recoveredKey = "Atbash fijo"
```

El texto resultante se puntúa con `LanguageAnalyzer` para llenar `score` e `idioma`.

#### Ejemplo

```text
Entrada:  ABC xyz!
Salida:   ZYX cba!
```

---

### 6.2 César

**ID:** `caesar`  
**Parámetro:** `shift`  
**Fórmula:**

```text
E(x) = (x + shift) mod 26
D(x) = (x - shift) mod 26
```

#### Cifrado y descifrado

1. Se exige un entero `shift`.
2. Se calcula `normalizedShift = mod(shift, 26)`.
3. Al cifrar se suma `normalizedShift`.
4. Al descifrar se resta `normalizedShift`.

El texto conserva el caso y todos los caracteres que no sean letras ASCII.

#### Break

El espacio de claves tiene solo 26 posibilidades:

```text
best = null
for shift in 0..25:
    candidatePlainText = decrypt(cipherText, shift)
    candidate = score(candidatePlainText)
    if best is null or candidate.score > best.score:
        best = candidate
return best
```

El `recoveredKey` es el desplazamiento utilizado para descifrar. En un cifrado creado con `shift = 11`, devuelve `"11"`.

#### Ejemplo

```text
Texto plano: HELLO
shift:        3
Cifrado:      KHOOR
Descifrado:   HELLO
```

---

### 6.3 ROT13

**ID:** `rot13`  
**Clave:** ninguna  
**Fórmula:**

```text
E(x) = (x + 13) mod 26
```

#### Cifrado y descifrado

Se desplaza siempre 13 posiciones. ROT13 también es involutivo:

```text
ROT13(ROT13(texto)) = texto
```

La operación `encrypt` y `decrypt` llaman a la misma transformación.

#### Break

Se aplica ROT13 una vez. El `recoveredKey` es:

```text
"13"
```

#### Ejemplo

```text
Entrada:  Hello, World!
Salida:   Uryyb, Jbeyq!
```

---

### 6.4 Sustitución monoalfabética

**ID:** `substitution`  
**Parámetro:** `key`  
**Fórmula:**

```text
E(x) = key[x]
```

La clave es un alfabeto completo de 26 letras. La posición de la clave corresponde a la letra original:

```text
Entrada: ABCDEFGHIJKLMNOPQRSTUVWXYZ
Clave:   QWERTYUIOPASDFGHJKLZXCVBNM
```

Así, `A -> Q`, `B -> W`, `C -> E`, etc.

#### Cifrado

1. Se normaliza la clave a mayúsculas.
2. Se valida que mida exactamente 26.
3. Se valida que no tenga duplicados.
4. Para cada letra se obtiene su índice y se consulta `key[index]`.

#### Descifrado

Se construye la tabla inversa:

```text
for plainIndex in 0..25:
    cipherIndex = key[plainIndex]
    inverse[cipherIndex] = plainIndex
```

Luego cada letra cifrada se transforma con `inverse`.

#### Break

El espacio de claves es `26!`, por lo que no se usa fuerza bruta. Se realiza una búsqueda heurística para español e inglés.

##### Paso 1: mapeo inicial por frecuencia

1. Se cuentan las letras del criptograma.
2. Se ordenan las letras cifradas por frecuencia descendente.
3. Se ordenan las letras esperadas del idioma por frecuencia descendente.
4. La letra cifrada más frecuente se asigna a la letra normal más frecuente, y así sucesivamente.

El mapeo interno es `cipherTextLetter -> plainTextLetter`.

##### Paso 2: reinicios

Para cada idioma se hacen 10 reinicios:

- Reinicio 0: utiliza el mapeo de frecuencia.
- Reinicios posteriores cuyo índice es múltiplo de 3: barajan completamente el mapeo.
- Los demás reinicios: hacen entre `4 + restart` intercambios aleatorios de valores.

El generador aleatorio se inicializa de forma determinista con el texto y el salt:

```text
Español: 17
Inglés:  31
```

##### Paso 3: subida voraz aleatoria

Se realizan 3500 iteraciones. En cada iteración:

1. Se escogen dos posiciones distintas.
2. Se intercambian sus valores.
3. Se descifra todo el texto con el mapeo candidato.
4. Se conserva el intercambio solo si aumenta el `score` actual.

##### Paso 4: recocido simulado

Se realizan 5000 iteraciones más. Se usa la misma operación de intercambio, pero también se puede aceptar un descenso:

```text
temperature = 0.22 * (1 - iteration / 5000) + 0.01
accept = candidateScore > currentScore
      || random() < exp((candidateScore - currentScore) / temperature)
```

Siempre se conserva por separado el mejor mapeo observado.

##### Paso 5: subida exhaustiva

Desde el mejor mapeo se hacen hasta 3 pasadas sobre todos los pares de letras (`26 * 25 / 2 = 325` pares). Un intercambio se conserva solo si mejora la puntuación. Una pasada sin mejora termina la fase.

##### Clave devuelta

El mapeo utilizado para romper va de cifrado a plano, pero la clave convencional va de plano a cifrado. Por eso se invierte antes de devolverla:

```text
recoveredKey[plainIndex] = cipherLetter
```

En textos cortos puede ser una aproximación y no necesariamente la clave original.

---

### 6.5 Afín

**ID:** `affine`  
**Parámetros:** `a`, `b`  

```text
E(x) = (a * x + b) mod 26
D(y) = a^-1 * (y - b) mod 26
```

`a^-1` es el inverso modular de `a` módulo 26. Solo son válidos los valores coprimos con 26:

```text
1, 3, 5, 7, 9, 11, 15, 17, 19, 21, 23, 25
```

#### Cifrado

1. Se leen `a` y `b` como enteros.
2. Se normalizan ambos módulo 26.
3. Se verifica `gcd(a, 26) == 1`.
4. Se aplica `a * x + b` a cada letra.

#### Descifrado

Se calcula el inverso modular de `a` probando candidatos de 1 a 25:

```text
for candidate in 1..25:
    if mod(a * candidate, 26) == 1:
        inverseA = candidate
```

Después se aplica `inverseA * (y - b)`.

#### Break

El espacio total es pequeño: `12 * 26 = 312` combinaciones.

```text
best = null
for a in 0..25:
    if gcd(a, 26) != 1:
        continue
    for b in 0..25:
        candidatePlainText = decrypt(cipherText, a, b)
        candidate = score(candidatePlainText)
        if best is null or candidate.score > best.score:
            best = candidate
return best
```

`recoveredKey` tiene el formato:

```text
a=5, b=8
```

#### Ejemplo

```text
Texto plano: AFFINE
a:            5
b:            8
Cifrado:      IHHWVC
```

---

### 6.6 Vigenère

**ID:** `vigenere`  
**Parámetro:** `key`  

```text
E(P_i, K_i) = (P_i + K_i) mod 26
D(C_i, K_i) = (C_i - K_i) mod 26
```

La clave se repite solo sobre las letras ASCII del texto.

#### Cifrado

```text
keyIndices = índices de la clave
letterPosition = 0

for character in text:
    if no es letra ASCII:
        copiar character
        continue

    k = keyIndices[letterPosition mod keyLength]
    output = character + k
    letterPosition++
```

#### Descifrado

Utiliza el mismo recorrido, pero resta `k` en vez de sumarlo.

#### Break

El análisis se encuentra en `PolyalphabeticCryptanalysis` y tiene cuatro fases.

##### Paso 1: extraer letras

Para el análisis se usa `LettersOnly`:

- Convierte minúsculas a mayúsculas.
- Descompone Unicode en forma D.
- Elimina marcas combinantes, por ejemplo tildes.
- Conserva solo `A-Z`.

La transformación normal de Vigenère, sin embargo, solo procesa letras ASCII. Esta diferencia debe conservarse si se busca paridad exacta con el backend original.

##### Paso 2: estimar longitudes

Se consideran longitudes de 1 a 20, limitadas a como máximo la mitad de las letras disponibles.

Se combinan:

- **Kasiski:** se buscan secuencias repetidas de longitud 3, 4 y 5. Las distancias entre repeticiones votan por sus factores de 2 hasta la longitud máxima.
- **Índice de coincidencia:** se separan las letras en columnas según cada longitud y se calcula:

```text
IC = sum(count[i] * (count[i] - 1)) / (n * (n - 1))
```

La puntuación de cada longitud es:

```text
combined = averageIC + 0.0025 * min(kasiskiVotes, 20)
```

Las longitudes se ordenan por `combined` descendente y, en empate, por longitud ascendente. Se procesan todas las candidatas, no solo la primera.

##### Paso 3: construir clave inicial

Para cada idioma y cada posición de la clave:

1. Se extrae la columna de criptograma.
2. Se prueban los 26 posibles valores de clave.
3. Para Vigenère se puntúa la columna `C - K` con chi-cuadrado contra las frecuencias del idioma.
4. Se conserva el valor con mejor puntuación.

Esto equivale a resolver cada columna como un César independiente.

##### Paso 4: optimizar la clave

Se hacen hasta 4 pasadas de subida coordenada:

```text
for pass in 0..3:
    improved = false
    for position in key:
        probar las 26 letras en position
        descifrar el texto completo
        conservar la letra que mejore el score global
    si no improved: terminar
```

La puntuación global utiliza letras, chi-cuadrado, bigramas, trigramas y palabras comunes.

El resultado se compara entre todos los idiomas y longitudes. Si la clave hallada es una repetición, se reduce a su período mínimo. Por ejemplo, `LEMONLEMON` se devuelve como `LEMON`.

`recoveredKey` es la clave periódica estimada, no una garantía de la clave original.

#### Ejemplo

```text
Texto plano: ATTACKATDAWN
Clave:       LEMON
Cifrado:     LXFOPVEFRNHR
```

---

### 6.7 Beaufort

**ID:** `beaufort`  
**Parámetro:** `key`  

```text
C = (K - P) mod 26
```

#### Cifrado y descifrado

Para cada letra se calcula `keyIndex - textIndex`. No hay una función separada de descifrado: la misma operación sirve en ambos sentidos.

```text
Beaufort(Beaufort(texto, clave), clave) = texto
```

Los espacios y signos conservan su posición y no avanzan la clave.

#### Break

Utiliza el mismo analizador de Vigenère, pero cambia las operaciones de columna:

```text
plaintextColumn = keyValue - cipherColumn
```

La clave inicial se obtiene con chi-cuadrado usando `K - C`, y la optimización descifra con:

```text
P = K - C
```

El resto del proceso es igual a Vigenère: Kasiski, índice de coincidencia, longitudes 1..20, ambos idiomas, 4 pasadas de optimización y normalización del período.

#### Ejemplo

```text
Texto plano: HELLO
Clave:       FORT
Cifrado:     YKGIR
```

---

### 6.8 Autokey / autoclave

**ID:** `autokey`  
**Parámetro:** `key`  

```text
E(P_i) = (P_i + K_i) mod 26
D(C_i) = (C_i - K_i) mod 26
```

La clave efectiva empieza con una semilla y después continúa con el texto plano.

#### Cifrado

Sea `seed` la clave inicial:

```text
si i < longitud(seed):
    K_i = seed[i]
si i >= longitud(seed):
    K_i = P_(i - longitud(seed))
```

Es decir, después de consumir la semilla se utiliza el texto plano que ya se conoce.

#### Descifrado

Durante el descifrado todavía no se conoce el texto plano completo. Se recupera una letra cada vez:

```text
si i < longitud(seed):
    K_i = seed[i]
si i >= longitud(seed):
    K_i = P_(i - longitud(seed)) recuperado anteriormente

P_i = C_i - K_i
```

Los caracteres que no sean letras ASCII se copian y no incrementan `i`.

#### Break

El período no es constante, por lo que no se resuelve como Vigenère. Se busca la semilla.

##### Longitudes y modelos

- Se extraen las letras del criptograma con `LettersOnly`.
- Se prueban longitudes de semilla de 1 hasta `min(20, cantidadDeLetras)`.
- Se intenta tanto modelo español como inglés.

##### Semillas iniciales

Para cada longitud se generan 8 candidatos:

1. Una semilla llena de `A`.
2. Una semilla por cada una de las 4 letras más frecuentes esperadas del idioma. Cada posición se calcula como:

```text
seed[position] = cipher[position] - expectedPlainLetter
```

3. Tres semillas aleatorias.

El generador determinista utiliza salt `47` para español y `61` para inglés.

##### Optimización de semilla

Para cada semilla:

1. Se descifra el texto completo y se calcula el score.
2. Se hacen hasta 3 pasadas coordenadas. Para cada posición se prueban las 26 letras y se conserva la que más mejora el texto.
3. Se hacen 350 iteraciones de recocido simulado:

```text
temperature = 0.35 * (1 - iteration / 350)
accept = candidateScore > currentScore
      || random() < exp((candidateScore - currentScore) / temperature)
```

4. Se hace una última subida voraz probando las 26 letras en cada posición.
5. Se devuelve la mejor semilla y el mejor texto observados, no necesariamente el estado final del recocido.

Si no hay ninguna letra, el método devuelve el texto sin cambios, semilla vacía y score mínimo.

---

### 6.9 Rail Fence

**ID:** `rail-fence`  
**Parámetro:** `rails`  

```text
0, 1, 2, ..., rails - 1, rails - 2, ..., 1, 0, ...
```

#### Construcción del patrón

```text
rail = 0
direction = +1

for position in 0..length-1:
    pattern[position] = rail

    if rail == 0:
        direction = +1
    else if rail == rails - 1:
        direction = -1

    rail += direction
```

#### Cifrado

1. Se genera el patrón zigzag.
2. Se crea un buffer por riel.
3. Cada carácter se añade al buffer indicado por `pattern[position]`.
4. Se concatenan los buffers de riel 0 a `rails - 1`.

No se eliminan espacios ni signos.

#### Descifrado

1. Se genera el mismo patrón para la longitud del criptograma.
2. Se cuenta cuántas posiciones pertenecen a cada riel.
3. Se corta el criptograma en esos tamaños y se asigna cada segmento a su riel.
4. Se recorre de nuevo el patrón zigzag y se extrae el siguiente carácter de cada riel.

#### Break

El número de rieles se desconoce, pero normalmente es pequeño:

```text
maximumRails = min(20, max(2, cipherText.length))
idioma = detectLanguage(cipherText)

for rails in 2..maximumRails:
    candidatePlainText = decrypt(cipherText, rails)
    candidate.score = score(candidatePlainText, idioma)
    conservar el mejor
```

`recoveredKey` es el número de rieles como texto, por ejemplo `"4"`.

Como es una transposición, se detecta el idioma una sola vez a partir del criptograma y todos los candidatos se puntúan con ese idioma.

#### Ejemplo

```text
Texto plano: WEAREDISCOVEREDFLEEATONCE
Rieles:      3
Cifrado:     WECRLTEERDSOEEFEAOCAIVDEN
```

---

### 6.10 Transposición Columnar

**ID:** `columnar`  
**Parámetro:** `key`  

#### Orden de columnas

La clave define una columna por carácter. Se ordenan por:

1. Carácter de la clave, ascendente.
2. Índice original, ascendente para desempatar caracteres repetidos.

Ejemplo con `ZEBRAS`:

```text
Columna original: 1 2 3 4 5 6
Clave:            Z E B R A S
Orden de lectura: 5 3 2 4 6 1
```

Para `BALLOON`, las dos `L` y las dos `O` conservan el orden de izquierda a derecha.

#### Cifrado

No se rellena la matriz. Conceptualmente, el texto se escribe por filas:

```text
for column in readOrder:
    for position = column; position < text.length; position += columnCount:
        output += text[position]
```

Esto equivale a leer cada columna completa en el orden definido por la clave.

#### Descifrado

Sea:

```text
rowCount = ceil(text.length / columnCount)
remainder = text.length mod columnCount
```

La longitud de una columna original es:

```text
if remainder == 0 or column < remainder:
    columnLength = rowCount
else:
    columnLength = rowCount - 1
```

Luego:

1. Se recorren las columnas en `readOrder`.
2. Se rellenan con el segmento correspondiente del criptograma.
3. Se recorre la matriz por filas y columnas originales.
4. Se concatenan las celdas existentes.

#### Break

El método no intenta recuperar la palabra de clave original, sino el número y orden de columnas.

```text
maximumColumns = min(20, max(2, cipherText.length))
maximumExhaustiveColumns = min(8, maximumColumns)
idioma = detectLanguage(cipherText)
```

##### Hasta 8 columnas: fuerza bruta

Para cada cantidad de columnas de 2 a 8 se prueban todas las permutaciones:

```text
for columnCount in 2..maximumExhaustiveColumns:
    for order in permutations(0..columnCount-1):
        candidatePlainText = decryptWithoutKey(cipherText, columnCount, order)
        candidate.score = score(candidatePlainText, idioma)
        conservar el mejor
```

Si se llega a 8 columnas, se evalúan `46.232` órdenes en total:

```text
2! + 3! + 4! + 5! + 6! + 7! + 8! = 46.232
```

##### Más de 8 columnas: búsqueda local

Para cada cantidad de columnas adicional hasta 20:

1. Se empieza con el orden identidad `0, 1, 2, ...`.
2. Se hacen 6 reinicios; los reinicios posteriores barajan el orden.
3. En cada reinicio se hacen 3500 intercambios aleatorios de dos posiciones.
4. Un intercambio solo se conserva si mejora la puntuación.
5. Se mantiene el mejor orden global.

El generador aleatorio usa el texto y `columnCount * 101 + 1` como salt.

##### Clave devuelta

El resultado tiene este formato, con columnas numeradas desde 1:

```text
columnas=6; orden=5, 3, 2, 4, 6, 1
```

Una permutación de columnas no permite deducir de forma fiable las letras exactas de la palabra usada como clave.

---

## 7. Analizador de idioma usado por todos los break

El analizador está en `LanguageAnalyzer.cs`. Es un modelo pequeño, deliberadamente educativo, no un corpus completo.

### 7.1 Extracción de letras

La función conceptual es:

```text
normalized = UnicodeNormalize(text, FormD)
eliminar caracteres Unicode NonSpacingMark
conservar A-Z y convertir a mayúsculas
```

Por esto `á` puede contribuir como `A` durante el análisis estadístico, aunque las funciones de cifrado normales no transformen `á`.

### 7.2 Componentes del score

Para un texto candidato se calcula:

```text
score = letterLogLikelihood
      - 0.65 * chiSquared
      + 0.85 * bigramScore
      + 1.15 * trigramScore
      + wordScore
```

#### Frecuencias de letras

Se cuentan las letras del texto. Para cada letra se calcula:

```text
expectedCount = languageFrequency[letter] * totalLetters
```

La verosimilitud de letras es:

```text
sum(observedCount * ln(languageFrequency[letter])) / totalLetters
```

El chi-cuadrado normalizado es:

```text
sum((observedCount - expectedCount)^2 / expectedCount) / totalLetters
```

#### Bigramas y trigramas

Se quitan los caracteres que no sean letras y se recorren ventanas de 2 y 3 letras. Los n-gramas conocidos tienen probabilidades calculadas por rango:

```text
weight = 1 / (rank + 1)
probability = weight / sum(all weights)
```

Los n-gramas desconocidos usan:

```text
unknownNgramProbability = 0.00015
```

Se promedia el logaritmo de las probabilidades de todas las ventanas.

#### Palabras comunes

El texto se separa por espacios. Cada palabra se normaliza con `LettersOnly`. La contribución es:

```text
wordScore = 0.8 * knownWords / totalWords
```

Si no hay palabras, esta contribución es cero.

### 7.3 Detección de idioma

Se calcula el score en ambos modelos:

```text
spanishScore = score(text, Espanol)
englishScore = score(text, Ingles)
```

Se elige español si hay empate:

```text
spanishScore >= englishScore ? Espanol : Ingles
```

### 7.4 Datos del modelo

Para una migración funcional se deben trasladar las siguientes tablas. Los vectores están en orden `A` a `Z`; el código original los normaliza dividiéndolos por la suma total.

```php
$spanishLetterValues = [
    0.1253, 0.0142, 0.0468, 0.0586, 0.1368, 0.0069, 0.0101,
    0.0070, 0.0625, 0.0044, 0.0002, 0.0497, 0.0315, 0.0671,
    0.0868, 0.0251, 0.0088, 0.0687, 0.0798, 0.0463, 0.0393,
    0.0090, 0.0001, 0.0022, 0.0090, 0.0052,
];

$englishLetterValues = [
    0.08167, 0.01492, 0.02782, 0.04253, 0.12702, 0.02228,
    0.02015, 0.06094, 0.06966, 0.00153, 0.00772, 0.04025,
    0.02406, 0.06749, 0.07507, 0.01929, 0.00095, 0.05987,
    0.06327, 0.09056, 0.02758, 0.00978, 0.02360, 0.00150,
    0.01974, 0.00074,
];
```

Bigramas en español:

```text
DE LA EL EN ES ER RE AR AS AL LO RA TA OS TE SE OR ON AD AN
CO CI CA QU UE NT IA AC DO TO EC MI NO MA PA PR TR BR PL CR
GR LL CH CE SI ME DI UN SU IO
```

Bigramas en inglés:

```text
TH HE IN ER AN RE ON AT EN ND TI ES OR TE OF ED IS IT AL AR
ST TO NT NG SE HA AS OU IO LE VE CO ME DE HI RI RO IC NE EA
RA CE LI CH LL BE MA SI OM UR
```

Trigramas en español:

```text
QUE LOS LAS DEL POR CON UNA EST ARA ENT ION CIO ONA ADO ALA
NTE RES ERO PER PAR SOB TRA PRO COM MEN TOR CUA MAR TIE ACI
ESE ESO EST IST ANT EMO ERA DES VER ALL ELL ILL ELL UND UNO
QUI IEN MAS SIN
```

Trigramas en inglés:

```text
THE AND ING HER ERE ENT THA NTH WAS ETH FOR DTH HES VER HIS
OFT STH OTH RES TER EST ERS ATI HAT ATE ALL EVE NOT ITH TIO
ONE OUR OUT ARE BUT HAD WIT THI TED ION MEN CON PRO COM YOU
WAS DEL ENT OFT ANY
```

Las listas se pasan por `Distinct` antes de crear las probabilidades. Por ejemplo, algunos valores repetidos en las listas de trigramas no generan entradas duplicadas.

Palabras frecuentes en español:

```text
A AL DE DEL EL ELLA EN ES ESTA ESTE HAY LA LAS LO LOS ME NO
PARA POR QUE SE SI SON SU SUS UN UNA UNO Y CON COMO MUNDO TEXTO
CLAVE MENSAJE ATAQUE AMANECER ANALISIS FRECUENCIA CIFRADO CIFRADOS
DEMUESTRA DEBIL DEBILES LLAVE LETRAS TIEMPO CORTO PATRONES REVELAN
CUANDO LARGO SUFICIENTE CLASE EXPLICA PORQUE HISTORICO HISTORICA
```

Palabras frecuentes en inglés:

```text
A AN AND ARE AS AT BE BUT BY CAN FOR FROM HE HER HIS I IF IN IS IT
ME MY NO NOT OF ON OR OUR SHE THE THEY THIS TO WAS WE WERE WHAT WHEN
WHERE WHO WITH YOU YOUR TEXT KEY MESSAGE ATTACK DAWN WORLD CRYPTOGRAPHY
CLASS EXPLAINS WHY HISTORICAL CIPHERS WEAK REPEATED HIDES LETTERS SHORT
TIME LANGUAGE PATTERNS REVEAL REVEALS LONG ENOUGH HISTORIC PATTERN
```

---

## 8. Helpers necesarios para el port a PHP

### 8.1 Transformar letras conservando el formato

```php
function transformLetters(string $text, callable $transform): string
{
    $output = '';

    foreach (str_split($text) as $character) {
        $index = letterIndex($character);

        if ($index === null) {
            $output .= $character;
            continue;
        }

        $output .= fromIndex($transform($index), $character);
    }

    return $output;
}
```

Para una demo ASCII esto reproduce el comportamiento original. Si se necesita soportar UTF-8 carácter por carácter, hay que adaptar el recorrido sin cambiar la regla de que solo `A-Z` y `a-z` son transformables.

### 8.2 Inverso modular

```php
function modularInverse(int $value, int $modulus = 26): int
{
    $normalized = mod($value, $modulus);

    for ($candidate = 1; $candidate < $modulus; $candidate++) {
        if (mod($normalized * $candidate, $modulus) === 1) {
            return $candidate;
        }
    }

    throw new InvalidArgumentException('No existe inverso modular.');
}
```

### 8.3 Orden columnar estable

```php
function columnOrder(string $key): array
{
    $indices = range(0, strlen($key) - 1);

    usort($indices, static function (int $left, int $right) use ($key): int {
        $byCharacter = $key[$left] <=> $key[$right];

        return $byCharacter !== 0 ? $byCharacter : $left <=> $right;
    });

    return $indices;
}
```

No se debe usar un ordenamiento que rompa los empates de forma aleatoria.

### 8.4 Registro y despacho

El equivalente conceptual del servicio es:

```php
final class CipherService
{
    /** @var array<string, ClassicCipher> */
    private array $ciphers;

    public function __construct(iterable $ciphers)
    {
        foreach ($ciphers as $cipher) {
            $this->ciphers[strtolower($cipher->id())] = $cipher;
        }
    }

    public function transform(
        string $algorithm,
        string $operation,
        string $text,
        array $parameters,
        bool $includeSteps = true,
    ): array {
        $cipher = $this->find($algorithm);
        $operation = strtolower(trim($operation));

        return match ($operation) {
            'encrypt', 'cifrar' => $cipher->encrypt($text, $parameters, $includeSteps),
            'decrypt', 'descifrar' => $cipher->decrypt($text, $parameters, $includeSteps),
            default => throw new InvalidArgumentException('Operación no válida.'),
        };
    }

    public function break(string $algorithm, string $cipherText): array
    {
        return $this->find($algorithm)->break($cipherText);
    }

    private function find(string $algorithm): ClassicCipher
    {
        $id = strtolower(trim($algorithm));

        if (!isset($this->ciphers[$id])) {
            throw new InvalidArgumentException('Algoritmo no existente.');
        }

        return $this->ciphers[$id];
    }
}
```

En Laravel se puede registrar las diez clases como servicios singleton o construir el arreglo en un provider. La elección de singleton no cambia la lógica; los algoritmos no guardan estado de una operación a otra.

---

## 9. Diferencias importantes al portar los break heurísticos

Los resultados de fuerza bruta deben ser equivalentes si se conserva el mismo score. Los resultados de búsqueda heurística pueden variar por estas razones:

1. El código original usa `Random` de .NET.
2. PHP `rand()` y `mt_rand()` no generan la misma secuencia.
3. Sustitución, Autokey y Columnar dependen de intercambios aleatorios.
4. Si dos candidatos obtienen el mismo score, el código original conserva el primero porque compara con `>` y no con `>=`.

Hay dos opciones:

### Opción A: port funcional

Implementar el mismo algoritmo, tablas, límites y fórmulas, pero usar el generador aleatorio de PHP. El texto recuperado normalmente será correcto en textos suficientemente largos, aunque `recoveredKey` y `score` pueden diferir en casos ambiguos.

### Opción B: paridad exacta

Implementar en PHP:

- El hash determinista original.
- Un generador compatible con la secuencia de `System.Random` de la versión de .NET utilizada.
- El mismo orden de generación de permutaciones.
- Los mismos criterios de desempate.

El hash usado para crear la semilla es conceptualmente:

```text
hash = 2166136261
para cada codeUnit del texto:
    hash = uint32(hash XOR codeUnit)
    hash = uint32(hash * 16777619)

hash = uint32(hash XOR uint32(salt * 2246822519))
seed = hash AND 0x7fffffff
```

El salt por algoritmo es:

| Algoritmo | Salt español | Salt inglés |
|---|---:|---:|
| Sustitución | 17 | 31 |
| Autokey | 47 | 61 |
| Columnar | `columnCount * 101 + 1` | No depende del idioma; se detecta una vez. |

Para una demo de Laravel se recomienda la opción A y documentar que el `break` es heurístico.

---

## 10. Vectores de prueba

Estos casos se encuentran en `ENIGMA.Server.Tests/CipherTests.cs` y permiten verificar el port.

| Algoritmo | Entrada | Parámetros | Resultado cifrado |
|---|---|---|---|
| Atbash | `ABC xyz!` | - | `ZYX cba!` |
| César | `HELLO` | `shift=3` | `KHOOR` |
| ROT13 | `Hello, World!` | - | `Uryyb, Jbeyq!` |
| Sustitución | `ABC xyz` | `key=QWERTYUIOPASDFGHJKLZXCVBNM` | `QWE bnm` |
| Afín | `AFFINE` | `a=5,b=8` | `IHHWVC` |
| Vigenère | `ATTACKATDAWN` | `key=LEMON` | `LXFOPVEFRNHR` |
| Beaufort | `HELLO` | `key=FORT` | `YKGIR` |
| Rail Fence | `WEAREDISCOVEREDFLEEATONCE` | `rails=3` | `WECRLTEERDSOEEFEAOCAIVDEN` |

Para Columnar, el test principal usa:

```text
Texto: WE ARE DISCOVERED!
Clave: BALLOON
```

Se verifica que el cifrado mantenga la longitud y que descifrar devuelva exactamente el texto original.

El test de Autokey usa `QUEEN` como semilla y los tests de round-trip usan también:

```text
Ataque al amanecer! 123
Ñandú.
```

Esto confirma que los cifrados de letras preservan los caracteres no ASCII y que las transposiciones mueven el texto completo.

### Casos de break

Para probar el criptoanálisis se recomienda utilizar textos largos, por ejemplo repetir varias veces un párrafo natural en español o inglés. Los tests originales repiten tres veces un texto inglés y esperan:

| Algoritmo | Clave recuperada esperada |
|---|---|
| Atbash | `Atbash fijo` |
| ROT13 | `13` |
| César con 11 | `11` |
| Afín con 5 y 8 | `a=5, b=8` |
| Vigenère con LEMON | `LEMON` |
| Beaufort con FORT | `FORT` |
| Autokey con QUEEN | `QUEEN` |
| Rail Fence con 4 | `4` |
| Columnar | El texto plano debe recuperarse; la clave devuelta es un orden de columnas. |

No se deben usar textos de una o dos palabras para probar `break`: el score puede escoger un candidato incorrecto aunque el algoritmo esté implementado correctamente.

---

## 11. Recomendación para la demo Laravel

1. Implementar primero `transform` para los diez métodos.
2. Añadir pruebas de round-trip: `decrypt(encrypt(text, key), key) === text`.
3. Portar `LanguageAnalyzer` antes de implementar los `break` estadísticos.
4. Implementar primero los break deterministas: Atbash, ROT13, César, Afín y Rail Fence.
5. Añadir Vigenère y Beaufort con Kasiski, IC y optimización de columnas.
6. Añadir Sustitución, Autokey y Columnar como búsquedas heurísticas.
7. Ejecutar los break largos fuera del request web o establecer límites de tiempo, porque una sustitución o una transposición grande puede consumir bastante CPU.
8. Mantener los algoritmos separados de `Crypt` de Laravel; esta funcionalidad es exclusivamente de laboratorio.

Una respuesta de la demo puede seguir siendo compatible con el frontend original si conserva las propiedades `result`, `keyUsed`, `explanation`, `steps`, `plainText`, `recoveredKey`, `score` e `idioma`.
