import {parser} from "lezer-liquidsoap";
import {foldInside, foldNodeProp, indentNodeProp, LanguageSupport, LRLanguage} from "@codemirror/language";
import {styleTags, tags as t} from "@lezer/highlight";

export function liquidsoap(): LanguageSupport {
    const parserWithMetadata = parser.configure({
        props: [
            styleTags({
                Identifier: t.variableName,
                Boolean: t.bool,
                String: t.string,
                LineComment: t.lineComment,
                "( )": t.paren
            }),
            indentNodeProp.add({
                Application: context => context.column(context.node.from) + context.unit
            }),
            foldNodeProp.add({
                Application: foldInside
            })
        ]
    });

    const liquidsoapLanguage: LRLanguage = LRLanguage.define({
        parser: parserWithMetadata,
        languageData: {
            commentTokens: {line: "#"}
        }
    });

    return new LanguageSupport(liquidsoapLanguage, []);
}
