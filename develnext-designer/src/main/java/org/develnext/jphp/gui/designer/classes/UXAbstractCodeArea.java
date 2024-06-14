package org.develnext.jphp.gui.designer.classes;

import javafx.event.EventHandler;
import javafx.geometry.Bounds;
import org.develnext.jphp.ext.javafx.classes.layout.UXRegion;
import org.develnext.jphp.gui.designer.GuiDesignerExtension;
import org.develnext.jphp.gui.designer.editor.syntax.AbstractCodeArea;
import org.fxmisc.richtext.model.Paragraph;
import php.runtime.annotation.Reflection.*;
import php.runtime.env.Environment;
import php.runtime.reflection.ClassEntity;

import java.util.Collection;

@Abstract
@Namespace(GuiDesignerExtension.NS)
public class UXAbstractCodeArea<T extends AbstractCodeArea> extends UXRegion<AbstractCodeArea> {
    public static class EventProvider extends org.develnext.jphp.ext.javafx.support.EventProvider<AbstractCodeArea> {
        public EventProvider() {
            setHandler("beforeChange", new Handler() {
                @Override
                public void set(AbstractCodeArea target, EventHandler eventHandler) {
                    target.setOnBeforeChange(eventHandler);
                }

                @Override
                public EventHandler get(AbstractCodeArea target) {
                    return target.getOnBeforeChange();
                }
            });

            setHandler("afterChange", new Handler() {
                @Override
                public void set(AbstractCodeArea target, EventHandler eventHandler) {
                    target.setOnAfterChange(eventHandler);
                }

                @Override
                public EventHandler get(AbstractCodeArea target) {
                    return target.getOnAfterChange();
                }
            });

            setHandler("paste", new Handler() {
                @Override
                public void set(AbstractCodeArea target, EventHandler eventHandler) {
                    target.setOnPaste(eventHandler);
                }

                @Override
                public EventHandler get(AbstractCodeArea target) {
                    return target.getOnPaste();
                }
            });
        }

        @Override
        public Class<AbstractCodeArea> getTargetClass() {
            return AbstractCodeArea.class;
        }
    }

    interface WrappedInterface {
        @Property int tabSize();
        @Property boolean showGutter();
        @Property double lineHeight();
        //@Property @Nullable PopupWindow popupWindow();

        void showPopup();
        void hidePopup();
        void forgetHistory();
    }

    public UXAbstractCodeArea(Environment env, T wrappedObject) {
        super(env, wrappedObject);
    }

    public UXAbstractCodeArea(Environment env, ClassEntity clazz) {
        super(env, clazz);
    }

    @Override
    public AbstractCodeArea getWrappedObject() {
        return super.getWrappedObject();
    }

    @Getter
    public String getSelectedText() {
        return getWrappedObject().getSelectedText();
    }

    @Setter
    public void setSelectedText(String value) {
        getWrappedObject().replaceSelection(value);
    }

    @Getter
    public String getText() {
        return getWrappedObject().getText();
    }

    @Setter
    public void setText(String text) {
        getWrappedObject().setText(text);
    }

    @Getter
    public boolean getEditable() {
        return getWrappedObject().isEditable();
    }

    @Setter
    public void setEditable(boolean value) {
        getWrappedObject().setEditable(value);
    }

    @Getter
    public int getCaretOffset() {
        return getWrappedObject().getCaretColumn();
    }

    @Getter
    public int getCaretLine() {
        return getWrappedObject().getCurrentParagraph();
    }

    @Getter
    public int getCaretPosition() {
        return getWrappedObject().getCaretPosition();
    }

    @Setter
    public void setCaretPosition(int value) {
        try {
            getWrappedObject().moveTo(value);
            getWrappedObject().requestFollowCaret();
        } catch (IndexOutOfBoundsException e) {
            // nop.
        }
    }

    @Getter
    public double getEstimatedScrollY() {
        return getWrappedObject().getEstimatedScrollY();
    }

    @Getter
    public double getEstimatedScrollX() {
        return getWrappedObject().getEstimatedScrollX();
    }

    @Getter
    public Bounds getCaretBounds() {
        return getWrappedObject().getCaretBounds().orElse(null);
    }

    @Signature
    public void scrollToPixel(double x, double y) {
        getWrappedObject().scrollToPixel(x, y);
    }

    @Signature
    public void scrollBy(double deltaX, double deltaY) {
        getWrappedObject().scrollBy(deltaX, deltaY);
    }

    @Signature
    public void moveTo(int line, int pos) {
        getWrappedObject().moveTo(line, pos);
    }

    @Signature
    public void moveTo(int line) {
        getWrappedObject().moveTo(line, getCaretOffset());
    }

    @Signature
    public void undo() {
        try {
            getWrappedObject().undo();
        } catch (IllegalArgumentException e) {
            ;//nop hotfix
        }
    }

    @Signature
    public void redo() {
        try {
            getWrappedObject().redo();
        } catch (IllegalArgumentException e) {
            ;// nop hotfix
        }
    }

    @Signature
    public void cut() {
        getWrappedObject().cut();
    }

    @Signature
    public void copy() {
        getWrappedObject().copy();
    }

    @Signature
    public void paste() {
        getWrappedObject().paste();
    }

    @Signature
    public boolean canUndo() {
        return getWrappedObject().isUndoAvailable();
    }

    @Signature
    public boolean canRedo() {
        return getWrappedObject().isRedoAvailable();
    }

    @Signature
    public void jumpToLine(int line, int pos) {
        try {
            getWrappedObject().moveTo(getWrappedObject().position(line, pos).toOffset());
        } catch (IndexOutOfBoundsException e) {
            getWrappedObject().moveTo(getText().length());
        }
        getWrappedObject().requestFollowCaret();
    }

    @Signature
    public void jumpToLineSpaceOffset(int line) {
        int pos = 0;

        if (getWrappedObject().getParagraphs().size() < line + 1) {
            return;
        }

        Paragraph<Collection<String>, String, Collection<String>> paragraph = getWrappedObject().getParagraph(line);

        String text = paragraph.getText();

        for (int i = 0; i < text.length(); i++) {
            if (!Character.isSpaceChar(text.charAt(i))) {
                break;
            }

            pos++;
        }

        getWrappedObject().moveTo(getWrappedObject().position(line, pos).toOffset());
        getWrappedObject().requestFollowCaret();
    }

    @Signature
    public void insertToCaret(String text) {
        getWrappedObject().insertText(getWrappedObject().getCaretPosition(), text);
    }

    @Signature
    public void insertText(int index, String text) {
        getWrappedObject().insertText(index, text);
    }

    @Signature
    public void replaceText(int from, int to, String text) {
        getWrappedObject().replaceText(from, to, text);
    }

    @Signature
    public void deleteText(int from, int to) {
        getWrappedObject().deleteText(from, to);
    }

    @Signature
    public void select(int position, int length) {
        getWrappedObject().selectRange(position, length);
    }

    @Signature
    public void selectAll() {
        getWrappedObject().selectAll();
    }

    @Signature
    public void setStylesheet(String filename) {
        getWrappedObject().setStylesheet(filename);
    }
}
