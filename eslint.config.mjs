import pluginVue from "eslint-plugin-vue";
import {defineConfigWithVueTs, vueTsConfigs} from "@vue/eslint-config-typescript";

export default defineConfigWithVueTs(
        pluginVue.configs['flat/essential'],
        vueTsConfigs.recommendedTypeChecked,
    {
        rules: {
            "@typescript-eslint/no-unused-vars": ["error", {
                varsIgnorePattern: "^_",
            }],

            "@typescript-eslint/no-redundant-type-constituents": "off",
            "@typescript-eslint/no-explicit-any": "off",

            "vue/multi-word-component-names": "off",
            "vue/require-default-prop": "off",

            "vue/html-indent": ["error", 4, {
                attribute: 1,
                baseIndent: 1,
                closeBracket: 0,
                alignAttributesVertically: true,
            }],

            "vue/no-v-html": "off",
        },
    }
);
