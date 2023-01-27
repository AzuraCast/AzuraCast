import {helpers} from '@vuelidate/validators';
import zxcvbn from "zxcvbn";

export default function validatePassword(value) {
    const result = zxcvbn(value);
    return !helpers.req(value) || result.score > 2;
}
