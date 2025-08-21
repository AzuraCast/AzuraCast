import {isFilled} from "@regle/rules";
import zxcvbn from "zxcvbn";

export default function validatePassword(value: string): boolean {
    const result = zxcvbn(value);
    return !isFilled(value) || result.score > 2;
}
