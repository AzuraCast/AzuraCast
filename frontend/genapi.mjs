import {execFileSync} from "node:child_process";
import path from "node:path";
import {generateApi} from "swagger-typescript-api";

const __dirname = import.meta.dirname;

const output = path.resolve(__dirname, "./entities");
const specUrl = process.env.GENERATE_API_URL ?? "http://localhost/api/openapi.yml";

const baseOptions = {
    fileName: "ApiInterfaces.ts",
    output,
    generateClient: false,
    generateUnionEnums: false,
    addReadonly: true
};

await generateApi({
    ...baseOptions,
    url: specUrl
});

const outputFile = path.join(output, baseOptions.fileName);
const biomeBin = path.resolve(__dirname, "../node_modules/.bin/biome");

execFileSync(biomeBin, ["check", "--write", outputFile], {stdio: "inherit"});
