
// Polyfill para CSS.supports - deve ser executado imediatamente
(function() {
    'use strict';

    // Verificar se CSS já existe e criar se necessário
    if (typeof window.CSS === 'undefined') {
        window.CSS = {};
    }

    // Implementar CSS.supports se não existir
    if (typeof window.CSS.supports === 'undefined') {
        window.CSS.supports = function(property, value) {
            // Se não há argumentos, retornar false
            if (arguments.length === 0) {
                return false;
            }

            var prop = property;
            var val = value;

            // Se só há um argumento, assumir formato "property: value"
            if (arguments.length === 1 && typeof property === 'string') {
                var declaration = property.trim();
                var colonIndex = declaration.indexOf(':');
                
                if (colonIndex === -1) {
                    return false;
                }

                prop = declaration.substring(0, colonIndex).trim();
                val = declaration.substring(colonIndex + 1).trim();
            }

            // Verificar se temos propriedade e valor válidos
            if (!prop || typeof prop !== 'string') {
                return false;
            }
            
            if (val === undefined || val === null) {
                return false;
            }

            // Converter valor para string se necessário
            val = String(val).trim();
            if (!val) {
                return false;
            }

            // Testar suporte usando elemento temporário
            try {
                var testElement = document.createElement('div');
                var testStyle = testElement.style;
                
                // Lista de prefixes para testar
                var prefixes = ['', '-webkit-', '-moz-', '-ms-', '-o-'];
                
                for (var i = 0; i < prefixes.length; i++) {
                    var prefixedProperty = prefixes[i] + prop;
                    
                    try {
                        // Tentar definir a propriedade
                        var originalValue = testStyle[prefixedProperty];
                        testStyle[prefixedProperty] = val;
                        
                        // Verificar se o valor foi aceito
                        var hasSupport = testStyle[prefixedProperty] && testStyle[prefixedProperty] !== originalValue;
                        
                        if (hasSupport) {
                            return true;
                        }
                    } catch (e) {
                        // Continuar tentando outros prefixes
                        continue;
                    }
                }
                
                return false;
            } catch (e) {
                // Em caso de erro, assumir que não há suporte
                return false;
            }
        };
    }

    // Implementar CSS.escape se não existir
    if (typeof window.CSS.escape === 'undefined') {
        window.CSS.escape = function(value) {
            if (arguments.length === 0) {
                throw new TypeError('CSS.escape requires an argument');
            }
            
            var string = String(value);
            var length = string.length;
            var index = -1;
            var codeUnit;
            var result = '';
            var firstCodeUnit = string.charCodeAt(0);
            
            while (++index < length) {
                codeUnit = string.charCodeAt(index);
                
                // Note: there's no need to special-case astral symbols, surrogate
                // pairs, or lone surrogates.
                
                // If the character is NULL (U+0000), then throw an
                // `InvalidCharacterError` exception and terminate these steps.
                if (codeUnit === 0x0000) {
                    throw new InvalidCharacterError(
                        'Invalid character: the input contains U+0000.'
                    );
                }
                
                if (
                    // If the character is in the range [\1-\1F] (U+0001 to U+001F) or is
                    // U+007F, [...]
                    (codeUnit >= 0x0001 && codeUnit <= 0x001F) || codeUnit == 0x007F ||
                    // If the character is the first character and is in the range [0-9]
                    // (U+0030 to U+0039), [...]
                    (index === 0 && codeUnit >= 0x0030 && codeUnit <= 0x0039) ||
                    // If the character is the second character and is in the range [0-9]
                    // (U+0030 to U+0039) and the first character is a `-` (U+002D), [...]
                    (
                        index === 1 &&
                        codeUnit >= 0x0030 && codeUnit <= 0x0039 &&
                        firstCodeUnit === 0x002D
                    )
                ) {
                    // http://dev.w3.org/csswg/cssom/#escape-a-character-as-code-point
                    result += '\\' + codeUnit.toString(16) + ' ';
                    continue;
                }
                
                // If the character is not handled by one of the above rules and is
                // greater than or equal to U+0080, is `-` (U+002D) or `_` (U+005F), or
                // is in one of the ranges [0-9] (U+0030 to U+0039), [A-Z] (U+0041 to
                // U+005A), or [a-z] (U+0061 to U+007A), [...]
                if (
                    codeUnit >= 0x0080 ||
                    codeUnit === 0x002D ||
                    codeUnit === 0x005F ||
                    codeUnit >= 0x0030 && codeUnit <= 0x0039 ||
                    codeUnit >= 0x0041 && codeUnit <= 0x005A ||
                    codeUnit >= 0x0061 && codeUnit <= 0x007A
                ) {
                    // the character itself
                    result += string.charAt(index);
                    continue;
                }
                
                // Otherwise, the escaped character.
                // http://dev.w3.org/csswg/cssom/#escape-a-character
                result += '\\' + string.charAt(index);
            }
            
            return result;
        };
    }

    // Garantir que os métodos estão disponíveis globalmente
    if (typeof window.CSS !== 'undefined') {
        // Congelar o objeto para evitar modificações
        try {
            Object.freeze(window.CSS);
        } catch (e) {
            // Ignorar erro se não for possível congelar
        }
    }
})();
