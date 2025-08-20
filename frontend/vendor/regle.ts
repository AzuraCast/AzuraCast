import {createRule, createScopedUseRegle, defineRegleConfig} from "@regle/core";
import {
    alpha,
    alphaNum,
    between,
    decimal,
    email,
    integer,
    maxLength,
    minLength,
    numeric,
    required,
    url,
    withMessage
} from "@regle/rules";
import {useTranslate} from "~/vendor/gettext.ts";
import validatePassword from "~/functions/validatePassword.ts";

export const isValidPassword = createRule({
    validator: (value: string) => validatePassword(value),
    message: 'This password is too common or insecure.',
});

export const isValidHexColor = createRule({
    validator: (value: string) => value === '' || /^#?[0-9A-F]{6}$/i.test(value),
    message: 'This field must be a valid, non-transparent 6-character hex color.',
});

export const {useRegle: useAppRegle} = defineRegleConfig({
    rules: () => {
        const {$gettext} = useTranslate();

        return {
            required: withMessage(
                required,
                $gettext('This field is required.')
            ),
            minLength: withMessage(minLength, ({$params: [min]}) => {
                return $gettext(
                    'This field must have at least %{min} letters.',
                    {
                        min: String(min)
                    }
                );
            }),
            maxLength: withMessage(maxLength, ({$params: [max]}) => {
                return $gettext(
                    'This field must have at most %{max} letters.',
                    {
                        max: String(max)
                    }
                );
            }),
            between: withMessage(between, ({$params: [min, max]}) => {
                return $gettext(
                    'This field must be between %{min} and %{max}.',
                    {
                        min: String(min),
                        max: String(max)
                    }
                );
            }),
            alpha: withMessage(
                alpha,
                $gettext('This field must only contain alphabetic characters.')
            ),
            alphaNum: withMessage(
                alphaNum,
                $gettext('This field must only contain alphanumeric characters.')
            ),
            numeric: withMessage(
                numeric,
                $gettext('This field must only contain numeric characters.')
            ),
            integer: withMessage(
                integer,
                $gettext('This field must be a valid integer.')
            ),
            decimal: withMessage(
                decimal,
                $gettext('This field must be a valid decimal number.')
            ),
            email: withMessage(
                email,
                $gettext('This field must be a valid e-mail address.')
            ),
            url: withMessage(
                url,
                $gettext('This field must be a valid URL.')
            ),
            isValidPassword: withMessage(
                isValidPassword,
                $gettext('This password is too common or insecure.')
            ),
            isValidHexColor: withMessage(
                isValidHexColor,
                $gettext('This field must be a valid, non-transparent 6-character hex color.')
            )
        };
    },
    modifiers: {
        autoDirty: false
    }
});

export const {
    useScopedRegle: useAppScopedRegle,
    useCollectScope: useAppCollectScope
} = createScopedUseRegle({customUseRegle: useAppRegle});
