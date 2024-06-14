package org.develnext.jphp.gui.designer.classes;

import org.develnext.jphp.gui.designer.GuiDesignerExtension;
import org.develnext.jphp.gui.designer.editor.syntax.impl.CssCodeArea;
import org.develnext.jphp.gui.designer.editor.syntax.impl.TextCodeArea;
import php.runtime.annotation.Reflection;
import php.runtime.annotation.Reflection.Signature;
import php.runtime.env.Environment;
import php.runtime.reflection.ClassEntity;

@Reflection.Namespace(GuiDesignerExtension.NS)
public class UXTextCodeArea<T extends TextCodeArea> extends UXAbstractCodeArea<TextCodeArea> {
    interface WrappedInterface {
    }

    public UXTextCodeArea(Environment env, T wrappedObject) {
        super(env, wrappedObject);
    }

    public UXTextCodeArea(Environment env, ClassEntity clazz) {
        super(env, clazz);
    }

    @Signature
    public void __construct() {
        __wrappedObject = new TextCodeArea();
    }
}
