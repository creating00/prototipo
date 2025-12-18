export class SelectUpdater {
    async update(selectId, data, fieldName, refreshOnSave, refreshUrl) {
        const helper = new ChoicesHelper(selectId);

        if (refreshOnSave && refreshUrl) {
            return this.refresh(selectId, refreshUrl);
        }

        const value = data.id;
        const label =
            data[fieldName] ||
            data.name ||
            data.title ||
            data.label ||
            Object.values(data).find((v) => typeof v === "string");

        if (value && label) {
            helper.addOption(value, label, true);
        }
    }

    async refresh(selectId, url) {
        const response = await fetch(url);
        if (!response.ok) return;

        const data = await response.json();

        const select = document.getElementById(selectId);
        const instance =
            select.closest("[x-data]")?.__x?.$data?.choices || select._choices;

        if (instance?.setChoices) {
            instance.setChoices(
                data.map((i) => ({
                    value: i.id,
                    label: i.name || i.title || i.label,
                })),
                "value",
                "label",
                false
            );
        }
    }
}
