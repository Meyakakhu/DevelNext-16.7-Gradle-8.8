package org.develnext.jphp.gui.designer.editor.syntax.impl;

import javafx.application.Platform;
import org.antlr.v4.runtime.*;
import org.develnext.jphp.core.compiler.jvm.JvmCompiler;
import org.develnext.jphp.core.syntax.SyntaxAnalyzer;
import org.develnext.jphp.core.tokenizer.TokenMeta;
import org.develnext.jphp.core.tokenizer.Tokenizer;
import org.develnext.jphp.core.tokenizer.token.*;
import org.develnext.jphp.core.tokenizer.token.Token;
import org.develnext.jphp.core.tokenizer.token.expr.BraceExprToken;
import org.develnext.jphp.core.tokenizer.token.expr.CommaToken;
import org.develnext.jphp.core.tokenizer.token.expr.OperatorExprToken;
import org.develnext.jphp.core.tokenizer.token.expr.operator.*;
import org.develnext.jphp.core.tokenizer.token.expr.value.*;
import org.develnext.jphp.core.tokenizer.token.stmt.ImplementsStmtToken;
import org.develnext.jphp.core.tokenizer.token.stmt.StmtToken;
import org.develnext.jphp.gui.designer.editor.syntax.AbstractCodeArea;
import org.develnext.jphp.gui.designer.editor.syntax.CodeAreaGutterNote;
import org.develnext.jphp.gui.designer.editor.syntax.hotkey.*;
import org.develnext.lexer.php.PHPLexer;
import org.develnext.lexer.php.PHPParser;
import org.fxmisc.richtext.model.StyleSpansBuilder;
import php.runtime.env.Context;
import php.runtime.env.Environment;
import php.runtime.env.handler.ExceptionHandler;
import php.runtime.exceptions.ParseException;
import php.runtime.lang.exception.BaseError;
import php.runtime.lang.exception.BaseParseError;

import java.io.File;
import java.io.IOException;
import java.util.*;


public class PhpCodeArea extends AbstractCodeArea {
    public static final List<String> SEMICOLON = Collections.singletonList("semicolon");
    public static final List<String> CONTROL = Collections.singletonList("control");
    public static final List<String> COMMENT = Collections.singletonList("comment");
    public static final List<String> STRING = Collections.singletonList("string");
    public static final List<String> NUMBER = Collections.singletonList("number");
    public static final List<String> VARIABLE = Collections.singletonList("variable");
    public static final List<String> KEYWORD = Collections.singletonList("keyword");
    public static final List<String> OPERATOR = Collections.singletonList("operator");

    public static final Map<Class<? extends Token>, List<String>> tokenStyles = new HashMap<Class<? extends Token>, List<String>>(){{
        put(SemicolonToken.class, SEMICOLON);

        put(ColonToken.class, CONTROL);
        put(CommaToken.class, CONTROL);
        put(BraceExprToken.class, CONTROL);

        put(CommentToken.class, COMMENT);

        put(StringExprToken.class, STRING);

        put(IntegerExprToken.class, NUMBER);
        put(DoubleExprToken.class, NUMBER);

        put(VariableExprToken.class, VARIABLE);

        put(StmtToken.class, KEYWORD);
        put(BooleanExprToken.class, KEYWORD);
        put(NullExprToken.class, KEYWORD);
        put(NewExprToken.class, KEYWORD);
        put(SelfExprToken.class, KEYWORD);
        put(StaticExprToken.class, KEYWORD);
        put(ParentExprToken.class, KEYWORD);
        put(EmptyExprToken.class, KEYWORD);
        put(IssetExprToken.class, KEYWORD);
        put(DieExprToken.class, KEYWORD);
        put(UnsetExprToken.class, KEYWORD);
        put(InstanceofExprToken.class, KEYWORD);
        put(CloneExprToken.class, KEYWORD);
        put(BooleanAnd2ExprToken.class, KEYWORD);
        put(BooleanOr2ExprToken.class, KEYWORD);
        put(BooleanXorExprToken.class, KEYWORD);
        put(OpenTagToken.class, KEYWORD);
        put(ImplementsStmtToken.class, KEYWORD);

        put(OperatorExprToken.class, OPERATOR);
    }};

    private final BaseErrorListener errorListener = new BaseErrorListener() {
        @Override
        public void syntaxError(Recognizer<?, ?> recognizer, Object offendingSymbol, int line, int charPositionInLine, String msg, RecognitionException e) {
            getGutter().addNote(line, new CodeAreaGutterNote("error", msg));
        }
    };
    private Environment environment = new Environment();

    public PhpCodeArea() {
        super();

        registerHotkey(new AddTabsHotkey());
        registerHotkey(new RemoveTabsHotkey());
        registerHotkey(new DuplicateSelectionHotkey());
        registerHotkey(new AutoSpaceEnterHotkey());
        registerHotkey(new AutoBracketsHotkey());
        registerHotkey(new BackspaceHotkey());

        setStylesheet(AbstractCodeArea.class.getResource("PhpCodeArea.css").toExternalForm());
    }

    private static Collection<String> getStyleOfToken(Token token) {
        List<String> result;

        Class<?> cls = token.getClass();
        boolean first = true;

        do {
            result = tokenStyles.get(cls);

            cls = cls.getSuperclass();

            if (result != null && !first) {
                tokenStyles.put(token.getClass(), result);
            }

            if (!Token.class.isAssignableFrom(cls)) {
                break;
            }

            first = false;
        } while (result == null);

        if (result == null) {
            if (token instanceof NameToken) {
                switch (token.getWord().toLowerCase()) {
                    case "array":
                        return KEYWORD;
                }
            }
        }

        return result == null ? Collections.emptyList() : result;
    }

    protected Thread lastCheckThread = null;

    @Override
    protected void computeHighlighting(StyleSpansBuilder<Collection<String>> spansBuilder, String text) {
        Tokenizer tokenizer;
        try {
            tokenizer = new Tokenizer(new Context(text));
        } catch (IOException e) {
            return;
        }

        //ANTLRInputStream inputStream = new ANTLRInputStream(text);
        //PHPLexer lex = new PHPLexer(inputStream);
        //lex.addErrorListener(errorListener);

        int lastEnd = 0;
        Token token;
        while ((token = tokenizer.nextToken()) != null) {
            TokenMeta meta = token.getMeta();
            int startIndex = meta.getStartIndex();

            /*if (token.getType() == PHPParser.Comment) {
                if (text.charAt(startIndex - 1) == '#') {
                    startIndex -= 1;
                } else if (text.charAt(startIndex - 1) == '/' && text.charAt(startIndex - 2) == '/') {
                    startIndex -= 2;
                }
            }*/

            int spacer = startIndex - lastEnd;

            if (spacer > 0) {
                spansBuilder.add(Collections.emptyList(), spacer);
            }

            Collection<String> styleOfToken = getStyleOfToken(token);

            int gap = meta.getEndIndex() - startIndex;
            spansBuilder.add(styleOfToken, gap);

            lastEnd = meta.getEndIndex();
        }

        Thread thread = lastCheckThread = new Thread() {
            @Override
            public void run() {
                try {
                    Thread.sleep(1000);
                } catch (InterruptedException e) {
                    return;
                }

                if (lastCheckThread != this) {
                    return;
                }

                try {
                    Environment env = environment;
                    SyntaxAnalyzer analyzer = new SyntaxAnalyzer(env, new Tokenizer(new Context(text, new File("source.php"))));
                    analyzer.getTree();
                } catch (IOException e) {
                    // throw new RuntimeException(e);
                } catch (BaseError e) {
                    Platform.runLater(() ->
                                    getGutter().addNote(
                                            e.getLine(environment).toInteger(),
                                            new CodeAreaGutterNote("error", e.getMessage(environment).toString() + " at pos " + e.getPosition(environment).toInteger())
                                    )
                    );

                    Platform.runLater(PhpCodeArea.this::refreshGutter);
                } catch (Throwable e) {
                    ;
                }

                /*lex.reset();
                PHPParser cssParser = new PHPParser(new CommonTokenStream(lex));
                cssParser.addErrorListener(errorListener);
                cssParser.htmlDocument();*/
            }
        };
        thread.start();
    }
}