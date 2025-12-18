export const configureGlobals = (dependencies) => {
    Object.entries(dependencies).forEach(([key, value]) => {
        window[key] = value;
    });
};
