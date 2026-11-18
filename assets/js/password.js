import { Strength } from '@tabler/core';

// Port of Symfony\Component\Validator\Constraints\PasswordStrengthValidator::estimateStrength()
// Keep in sync with symfony/validator. PHP works on bytes, so we encode the password as UTF-8 first.
let estimatePasswordStrength = function (password) {
    const bytes = new TextEncoder().encode(password);
    const length = bytes.length;
    if (!length) {
        return { score: 0, entropy: 0, length: 0 };
    }

    const counts = new Map();
    for (const b of bytes) {
        counts.set(b, (counts.get(b) || 0) + 1);
    }
    const chars = counts.size;

    let control = 0, digit = 0, upper = 0, lower = 0, symbol = 0, other = 0;
    for (const chr of counts.keys()) {
        if (chr < 32 || chr === 127) {
            control = 33;
        } else if (chr >= 48 && chr <= 57) {
            digit = 10;
        } else if (chr >= 65 && chr <= 90) {
            upper = 26;
        } else if (chr >= 97 && chr <= 122) {
            lower = 26;
        } else if (chr >= 128) {
            other = 128;
        } else {
            symbol = 33;
        }
    }

    const pool = lower + upper + digit + symbol + control + other;
    const entropy = chars * Math.log2(pool) + (length - chars) * Math.log2(chars);

    let score = 0;
    if (entropy >= 120) {
        score = 4;
    } else if (entropy >= 100) {
        score = 3;
    } else if (entropy >= 80) {
        score = 2;
    } else if (entropy >= 60) {
        score = 1;
    }

    return {
        score: score,
        entropy: entropy,
        length: [...password].length, // Characters for the length hint, not bytes
    };
};

const initPasswordStrengthMeters = function () {
    $('[data-password-strength]').each(function () {
        const meter = $(this);
        const selector = meter.data('passwordStrength');
        const input = $(selector);
        if (!input.length) {
            return;
        }

        const minLength = parseInt(input.data('minLength'), 10) || 0;
        const minStrength = parseInt(input.data('minStrength'), 10) || 0;

        const messages = {};
        for (const level of ['none', 'weak', 'fair', 'good', 'strong']) {
            const key = 'label' + level.charAt(0).toUpperCase() + level.slice(1);
            if (meter.data(key)) {
                messages[level] = meter.data(key);
            }
        }

        Strength.getOrCreateInstance(meter[0], {
            input: selector,
            messages: messages,
            // Symfony score 1..4 = weak, fair, good, strong (same as the settings);
            // score 0 (very weak) and too short = no level
            thresholds: [1, 2, 3],
            scorer: (password) => {
                const result = estimatePasswordStrength(password);
                if (result.length < minLength) {
                    return 0;
                }

                return result.score;
            },
        });

        // Additional hint (missing characters / too weak); Tabler only fires an event on level changes
        const hint = meter.parent().find('.password-strength-hint');
        if (hint.length) {
            input.on('input', () => {
                let text = '';

                if (input.val()) {
                    const result = estimatePasswordStrength(input.val());
                    const missingChars = minLength - result.length;

                    if (missingChars > 0) {
                        text = hint.data('hintLength').replace('%count%', missingChars);
                    } else if (result.score < minStrength) {
                        text = hint.data('hintStrength');
                    }
                }

                hint.text(text);
            });
        }
    });
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initPasswordStrengthMeters);
} else {
    initPasswordStrengthMeters();
}
