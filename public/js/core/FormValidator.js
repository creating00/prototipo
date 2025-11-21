class FormValidator {
    static validateRequired(fields) {
        const errors = [];

        fields.forEach((field) => {
            const element = document.querySelector(field.selector);
            if (!element || !element.value.trim()) {
                errors.push(field.message);
            }
        });

        return errors;
    }

    static validateNumber(fields) {
        const errors = [];

        fields.forEach((field) => {
            const element = document.querySelector(field.selector);
            if (element && element.value) {
                const value = parseFloat(element.value);
                if (isNaN(value) || value < 0) {
                    errors.push(field.message);
                }
            }
        });

        return errors;
    }
}
