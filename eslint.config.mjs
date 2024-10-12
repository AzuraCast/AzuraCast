import js from "@eslint/js";
import pluginVue from "eslint-plugin-vue";
import vueTsEslintConfig from "@vue/eslint-config-typescript";

export default [
    js.configs.recommended,
    ...pluginVue.configs["flat/essential"],
    ...vueTsEslintConfig({}),
    {
        rules: {
            "@typescript-eslint/no-unused-vars": ["error", {
                varsIgnorePattern: "^_|props",
            }],

            "@typescript-eslint/no-explicit-any": "off",
            "vue/multi-word-component-names": "off",

            "vue/html-indent": ["error", 4, {
                attribute: 1,
                baseIndent: 1,
                closeBracket: 0,
                alignAttributesVertically: true,
            }],

            "vue/no-v-html": "off",
            "vue/no-mutating-props": "off",
            "vue/no-multiple-template-root": "off",
            "vue/no-setup-props-destructure": "off",
        },
    }
];
