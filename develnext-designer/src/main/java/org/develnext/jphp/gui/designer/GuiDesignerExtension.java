package org.develnext.jphp.gui.designer;

import org.develnext.jphp.ext.javafx.JavaFXExtension;
import org.develnext.jphp.gui.designer.classes.*;
import org.develnext.jphp.gui.designer.editor.syntax.AbstractCodeArea;
import org.develnext.jphp.gui.designer.editor.syntax.impl.*;
import org.develnext.jphp.gui.designer.editor.tree.AbstractDirectoryTreeSource;
import org.develnext.jphp.gui.designer.editor.tree.DirectoryTreeValue;
import org.develnext.jphp.gui.designer.editor.tree.DirectoryTreeView;
import org.develnext.jphp.gui.designer.editor.tree.FileDirectoryTreeSource;
import org.fxmisc.flowless.VirtualizedScrollPane;
import org.fxmisc.richtext.InlineCssTextArea;
import php.runtime.env.CompileScope;

public class GuiDesignerExtension extends JavaFXExtension {
    public static final String NS = JavaFXExtension.NS + "designer";
    public static final String NS_DOCK = JavaFXExtension.NS + "dock";

    @Override
    public Status getStatus() {
        return Status.EXPERIMENTAL;
    }

    @Override
    public void onRegister(CompileScope scope) {
        registerClass(scope, UXDesigner.class);
        registerClass(scope, UXDesignPane.class);
        registerClass(scope, UXDesignProperties.class);
        registerClass(scope, UXDesignPropertyEditor.class);

        registerWrapperClass(scope, InlineCssTextArea.class, UXRichTextArea.class);
        registerWrapperClass(scope, AbstractCodeArea.class, UXAbstractCodeArea.class);
        registerWrapperClass(scope, VirtualizedScrollPane.class, UXCodeAreaScrollPane.class);
        registerWrapperClass(scope, TextCodeArea.class, UXTextCodeArea.class);
        registerWrapperClass(scope, CssCodeArea.class, UXCssCodeArea.class);
        registerWrapperClass(scope, FxCssCodeArea.class, UXFxCssCodeArea.class);
        registerWrapperClass(scope, PhpCodeArea.class, UXPhpCodeArea.class);
        registerWrapperClass(scope, JavaScriptCodeArea.class, UXJavaScriptCodeArea.class);

        //registerWrapperClass(scope, DockPane.class, UXDockPane.class);
        //registerWrapperClass(scope, DockNode.class, UXDockNode.class);

        registerWrapperClass(scope, DirectoryTreeValue.class, UXDirectoryTreeValue.class);
        registerWrapperClass(scope, AbstractDirectoryTreeSource.class, UXAbstractDirectoryTreeSource.class);
        registerWrapperClass(scope, FileDirectoryTreeSource.class, UXFileDirectoryTreeSource.class);
        registerWrapperClass(scope, DirectoryTreeView.class, UXDirectoryTreeView.class);


        registerClass(scope, FileSystemWatcher.WrapWatchKey.class);
        registerClass(scope, FileSystemWatcher.class);

        registerEventProvider(new UXAbstractCodeArea.EventProvider());

        //DockPane.initializeDefaultUserAgentStylesheet();
    }
}
