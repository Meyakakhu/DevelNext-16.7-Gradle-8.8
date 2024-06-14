package org.develnext.jphp.gui.designer.editor.syntax;

import javafx.application.Platform;
import javafx.concurrent.Task;
import javafx.event.ActionEvent;
import javafx.event.Event;
import javafx.event.EventHandler;
import javafx.scene.input.*;
import org.develnext.jphp.gui.designer.editor.inspect.AbstractInspector;
import org.develnext.jphp.gui.designer.editor.syntax.hotkey.*;
import org.develnext.jphp.gui.designer.editor.syntax.popup.CodeAreaContextMenu;
import org.develnext.jphp.gui.designer.editor.syntax.popup.CodeAreaPopup;
import org.fxmisc.flowless.VirtualFlow;
import org.fxmisc.richtext.CodeArea;
import org.fxmisc.richtext.model.RichTextChange;
import org.fxmisc.richtext.model.StyleSpans;
import org.fxmisc.richtext.model.StyleSpansBuilder;
import org.fxmisc.wellbehaved.event.EventPattern;
import org.fxmisc.wellbehaved.event.InputMap;
import org.fxmisc.wellbehaved.event.Nodes;

import java.time.Duration;
import java.util.*;
import java.util.concurrent.ExecutorService;
import java.util.concurrent.Executors;
import java.util.function.Predicate;

import static org.fxmisc.wellbehaved.event.EventPattern.keyPressed;
import static org.fxmisc.wellbehaved.event.EventPattern.keyReleased;
import static org.fxmisc.wellbehaved.event.EventPattern.mouseClicked;

abstract public class AbstractCodeArea extends CodeArea {
    private ExecutorService executor;

    private int tabSize;
    private boolean showGutter;
    private String stylesheet;

    protected final Set<AbstractHotkey> hotkeys = new HashSet<>();
    protected final Map<AbstractHotkey, InputMap<KeyEvent>> hotkeyHandlers = new LinkedHashMap<>();

    private CodeAreaGutter gutter = CodeAreaGutter.get(this);
    private CodeAreaPopup popup = new CodeAreaPopup();
    private CodeAreaContextMenu contextMenu = new CodeAreaContextMenu(this);

    private AbstractInspector inspector;

    private EventHandler<ActionEvent> onBeforeChange;
    private EventHandler<ActionEvent> onAfterChange;
    private EventHandler<ActionEvent> onPaste;

    private double fontSize;

    public AbstractCodeArea() {
        super();
        setTabSize(4);
        setShowGutter(true);

        setOnContextMenuRequested(e -> {
            contextMenu.show(this, e.getScreenX(), e.getScreenY());
        });


        Nodes.addInputMap(this, InputMap.consume(keyPressed(KeyCode.ESCAPE), e -> contextMenu.hide()));
        Nodes.addInputMap(this, InputMap.consume(mouseClicked(MouseButton.PRIMARY), e -> contextMenu.hide()));

        /*setPopupAlignment(PopupAlignment.CARET_BOTTOM);
        setPopupAnchorOffset(new Point2D(4, 4));*/

        getStyleClass().addAll("syntax-text-area");

        executor = Executors.newSingleThreadExecutor();

        richChanges()
                .filter(new Predicate<RichTextChange<Collection<String>, String, Collection<String>>>() {
                    @Override
                    public boolean test(RichTextChange<Collection<String>, String, Collection<String>> ch) {
                        return !ch.getInserted().equals(ch.getRemoved());
                    }
                }) // XXX
                .successionEnds(Duration.ofMillis(200))
                .supplyTask(AbstractCodeArea.this::computeHighlightingAsync)
                .awaitLatest(richChanges())
                .filterMap(t -> {
                    if (t.isSuccess()) {
                        return Optional.of(t.get());
                    } else {
                        //t.getFailure().printStackTrace();
                        return Optional.empty();
                    }
                })
                .subscribe(this::applyHighlighting);

        Nodes.addInputMap(this, InputMap.consume(keyReleased(), e -> {
            if (!isEditable()) {
                return;
            }

            if (onAfterChange != null) {
                onAfterChange.handle(new ActionEvent(this, this));
            }

            if (popup.isShowing() && (e.getCode() == KeyCode.LEFT || e.getCode() == KeyCode.RIGHT)) {
                hidePopup();
            }

            if (e.getText().isEmpty()) return;

            int position = this.getCaretPosition();
            /*if (this.getText(position - 3, position).equals("-fx")) {
                showPopup();
            } else {
                hidePopup();
            }*/
        }));

        focusedProperty().addListener((observable, oldValue, newValue) -> {
            if (!newValue) hidePopup();
        });

        setStylesheet(null);
    }

    public double getFontSize() {
        return fontSize;
    }

    public void setFontSize(double fontSize) {
        this.fontSize = fontSize;
    }

    @Override
    public void paste() {
        super.paste();

        if (onPaste != null) {
            onPaste.handle(new ActionEvent(this, this));
        }
    }

    public EventHandler<ActionEvent> getOnPaste() {
        return onPaste;
    }

    public void setOnPaste(EventHandler<ActionEvent> onPaste) {
        this.onPaste = onPaste;
    }

    public double getLineHeight() {
        VirtualFlow<?, ?> vf = (VirtualFlow<?, ?>) this.lookup(".virtual-flow");

        if (vf != null && !vf.visibleCells().isEmpty()) {
            return vf.visibleCells().get(0).getNode().getLayoutBounds().getHeight();
        }

        return 0;
    }

    public EventHandler<ActionEvent> getOnBeforeChange() {
        return onBeforeChange;
    }

    public void setOnBeforeChange(EventHandler<ActionEvent> onBeforeChange) {
        this.onBeforeChange = onBeforeChange;
    }

    public EventHandler<ActionEvent> getOnAfterChange() {
        return onAfterChange;
    }

    public void setOnAfterChange(EventHandler<ActionEvent> onAfterChange) {
        this.onAfterChange = onAfterChange;
    }

    public CodeAreaPopup getPopup() {
        return popup;
    }

    public void showPopup() {
        popup.show(this.getScene().getWindow());
    }

    public void hidePopup() {
        popup.hide();
    }

    public AbstractInspector getInspector() {
        return inspector;
    }

    public void setInspector(AbstractInspector inspector) {
        this.inspector = inspector;
    }

    abstract protected void computeHighlighting(StyleSpansBuilder<Collection<String>> spansBuilder, String text);

    public void registerHotkey(KeyCode keyCode, KeyCombination.Modifier[] keyModifiers, AbstractHotkey hotkey) {
        if (hotkey == null) {
            throw new NullPointerException("Hotkey is null");
        }

        if (!hotkeys.add(hotkey)) {
            throw new IllegalArgumentException("Hotkey already registered");
        }

        EventPattern<Event, KeyEvent> eventPattern = keyCode == null ? keyReleased() : keyPressed(keyCode, keyModifiers);

        InputMap<KeyEvent> inputMap = InputMap.sequence(
                InputMap.consume(eventPattern, keyEvent -> {
                    if (isEditable()) {
                        if (!keyEvent.isShortcutDown() || hotkey.isAllowShortcutDown()) {
                            if (hotkey.apply(this, keyEvent)) {
                                if (hotkey.isAffectsUndoManager()) {
                                    this.getUndoManager().mark();
                                }
                            }
                        }
                    }
                })
        );

        Nodes.addInputMap(this, inputMap);

        hotkeyHandlers.put(hotkey, inputMap);
    }

    public void forgetHistory() {
        getUndoManager().forgetHistory();
    }

    public void registerHotkey(AbstractHotkey hotkey) {
        registerHotkey(hotkey.getDefaultKeyCode(), hotkey.getDefaultKeyCombination(), hotkey);
    }

    public void unregisterHotkey(AbstractHotkey hotkey) {
        InputMap<KeyEvent> handler = hotkeyHandlers.get(hotkey);

        if (handler == null) {
            throw new IllegalArgumentException("Hotkey is not registered");
        }

        Nodes.removeInputMap(this, handler);
    }

    public void setStylesheet(String resource) {
        getStylesheets().clear();
        getStylesheets().add(AbstractCodeArea.class.getResource("AbstractCodeArea.css").toExternalForm());

        if (popup != null) {
            popup.getList().getStylesheets().add(AbstractCodeArea.class.getResource("AbstractCodeArea.css").toExternalForm());
        }

        if (resource != null && !resource.isEmpty()) {
            getStylesheets().add(resource);
            popup.getList().getStylesheets().add(resource);
        }

        stylesheet = resource;
    }

    public String getStylesheet() {
        return stylesheet;
    }

    public void setText(String text) {
        clear();
        appendText(text);

        applyHighlighting(computeHighlighting(text));
    }

    public int getTabSize() {
        return tabSize;
    }

    public void setTabSize(int tabSize) {
        this.tabSize = tabSize;
    }

    public boolean isShowGutter() {
        return showGutter;
    }

    public CodeAreaGutter getGutter() {
        return gutter;
    }

    public void setShowGutter(boolean showGutter) {
        this.showGutter = showGutter;
        if (showGutter) {
            setParagraphGraphicFactory(gutter);
        } else {
            setParagraphGraphicFactory(null);
        }
    }

    @Deprecated
    public void refreshGutter() {
        setParagraphGraphicFactory(null);
        gutter = getGutter().duplicate();
        setParagraphGraphicFactory(gutter);
    }

    private void applyHighlighting(StyleSpans<Collection<String>> highlighting) {
        try {
            setStyleSpans(0, highlighting);
        } catch (IllegalArgumentException | IndexOutOfBoundsException e) {
            //System.err.println(e.getMessage());
        }
    }

    private Task<StyleSpans<Collection<String>>> computeHighlightingAsync() {
        String text = getText();
        Task<StyleSpans<Collection<String>>> task = new Task<StyleSpans<Collection<String>>>() {
            @Override
            protected StyleSpans<Collection<String>> call() throws Exception {
                return computeHighlighting(text);
            }
        };
        executor.execute(task);
        return task;
    }

    private StyleSpans<Collection<String>> computeHighlighting(String text) {
        StyleSpansBuilder<Collection<String>> spansBuilder = new StyleSpansBuilder<>();

        getGutter().clearNotes();

        if(text.length() > 0){
            spansBuilder.add(Collections.emptyList(), 0);

            computeHighlighting(spansBuilder, text);
        } else {
            spansBuilder.add(Collections.emptyList(), 0);
        }

        Platform.runLater(this::refreshGutter);

        return spansBuilder.create();
    }
}
