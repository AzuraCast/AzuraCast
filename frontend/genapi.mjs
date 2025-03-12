import path from "node:path";
import {generateApi} from "swagger-typescript-api";

const __dirname = import.meta.dirname;

generateApi({
    name: "ApiInterfaces.ts",
    output: path.resolve(__dirname, "./entities"),
    url: "http://localhost/api/openapi.yml",
    generateClient: false,
    generateUnionEnums: false,
    addReadonly: true
});
