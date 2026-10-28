// custom_virtual_keyboard.js

class CustomVirtualKeyboard {
  constructor(
    keyboardContainerId = "custom-virtual-keyboard",
    inputSelector = "input:not([readonly]):not([disabled]):not(.select2-search__field), textarea:not([readonly]):not([disabled])"
  ) {
    this.keyboardContainerId = keyboardContainerId;
    this.keyboardContainer = null;
    this.inputSelector = inputSelector;
    this.activeInput = null;
    this.isShift = false;
    this.isCapsLock = false;
    this.select2SearchSelector =
      ".select2-container--open .select2-search__field";
    this.originalSelect2Close = null;
    this.isClickingKeyboard = false; // Flag for focus management
    this.lastViewportHeight = window.visualViewport
      ? window.visualViewport.height
      : window.innerHeight;
    this.isNativeKeyboardOpen = false; // Flag to track native keyboard status
    this.isMobileDevice = this.detectMobileDevice(); // Detect mobile device

    this.layouts = {
      default: [
        [
          "`",
          "1",
          "2",
          "3",
          "4",
          "5",
          "6",
          "7",
          "8",
          "9",
          "0",
          "-",
          "=",
          "{bksp}",
        ],
        [
          "{tab}",
          "q",
          "w",
          "e",
          "r",
          "t",
          "y",
          "u",
          "i",
          "o",
          "p",
          "[",
          "]",
          "\\",
        ],
        [
          "{capslock}",
          "a",
          "s",
          "d",
          "f",
          "g",
          "h",
          "j",
          "k",
          "l",
          ";",
          "'",
          "{enter}",
        ],
        [
          "{shift}",
          "z",
          "x",
          "c",
          "v",
          "b",
          "n",
          "m",
          ",",
          ".",
          "/",
          "{shift}",
        ],
        [".com", "@", "{space}", "{closeKeyboard}", "{toNum}"],
      ],
      shift: [
        [
          "~",
          "!",
          "@",
          "#",
          "$",
          "%",
          "^",
          "&",
          "*",
          "(",
          ")",
          "_",
          "+",
          "{bksp}",
        ],
        [
          "{tab}",
          "Q",
          "W",
          "E",
          "R",
          "T",
          "Y",
          "U",
          "I",
          "O",
          "P",
          "{",
          "}",
          "|",
        ],
        [
          "{capslock}",
          "A",
          "S",
          "D",
          "F",
          "G",
          "H",
          "J",
          "K",
          "L",
          ":",
          '"',
          "{enter}",
        ],
        [
          "{shift}",
          "Z",
          "X",
          "C",
          "V",
          "B",
          "N",
          "M",
          "<",
          ">",
          "?",
          "{shift}",
        ],
        [".com", "@", "{space}", "{closeKeyboard}", "{toNum}"],
      ],
      number: [
        ["1", "2", "3"],
        ["4", "5", "6"],
        ["7", "8", "9"],
        ["{toABC}", "0", ".", "{bksp}"],
        ["{closeKeyboard}"],
      ],
    };

    // Bind 'this' context for all event handlers
    this.handleFocus = this.handleFocus.bind(this);
    this.handleBlur = this.handleBlur.bind(this);
    this.handleButtonClick = this.handleButtonClick.bind(this);
    this.handleKeyboardMousedown = this.handleKeyboardMousedown.bind(this);
    this.handleKeyboardMouseup = this.handleKeyboardMouseup.bind(this);
    this.onSelect2OpenGlobal = this.onSelect2OpenGlobal.bind(this);
    this.onSelect2CloseGlobal = this.onSelect2CloseGlobal.bind(this);
    this.handleVisualViewportResize =
      this.handleVisualViewportResize.bind(this);

    this.init();
  }

  detectMobileDevice() {
    const toMatch = [
      /Android/i,
      /webOS/i,
      /iPhone/i,
      /iPad/i,
      /iPod/i,
      /BlackBerry/i,
      /Windows Phone/i,
    ];
    const isSmallScreen = window.matchMedia("(max-width: 768px)").matches;
    return (
      toMatch.some((toMatchItem) => {
        return navigator.userAgent.match(toMatchItem);
      }) || isSmallScreen
    );
  }

  init() {
    console.log("CustomVirtualKeyboard: Initializing...");
    if (this.isMobileDevice) {
      this.createKeyboardContainer();
      this.renderKeyboard();
      this.attachGlobalInputListeners();
      this.attachSelect2GlobalListeners();
      this.attachViewportResizeListener();
      console.log(
        "CustomVirtualKeyboard: Initialization complete for mobile device."
      );
    } else {
      console.log(
        "CustomVirtualKeyboard: Not a mobile device, custom keyboard will not be active."
      );
    }
  }

  createKeyboardContainer() {
    this.keyboardContainer = document.getElementById(this.keyboardContainerId);
    if (!this.keyboardContainer) {
      this.keyboardContainer = document.createElement("div");
      this.keyboardContainer.id = this.keyboardContainerId;
      document.body.appendChild(this.keyboardContainer);
      console.log(
        `CustomVirtualKeyboard: Created new container #${this.keyboardContainerId}`
      );
    } else {
      console.log(
        `CustomVirtualKeyboard: Found existing container #${this.keyboardContainerId}`
      );
    }
    this.keyboardContainer.style.display = "none";
    this.keyboardContainer.classList.add("custom-virtual-keyboard");

    // Capture phase for mousedown to ensure it fires before input blur
    this.keyboardContainer.addEventListener(
      "mousedown",
      this.handleKeyboardMousedown,
      true
    );
    this.keyboardContainer.addEventListener(
      "mouseup",
      this.handleKeyboardMouseup
    );
  }

  renderKeyboard(layoutName = "default") {
    if (!this.keyboardContainer) return;

    // Remove old listeners to prevent memory leaks and duplicate events
    this.keyboardContainer
      .querySelectorAll(".keyboard-button")
      .forEach((button) => {
        button.removeEventListener("click", this.handleButtonClick);
      });

    this.keyboardContainer.innerHTML = ""; // Clear existing buttons

    const currentLayout = this.layouts[layoutName];
    if (!currentLayout) {
      console.error(`CustomVirtualKeyboard: Layout "${layoutName}" not found.`);
      return;
    }

    currentLayout.forEach((rowKeys) => {
      const rowDiv = document.createElement("div");
      rowDiv.classList.add("keyboard-row");
      rowKeys.forEach((key) => {
        const button = document.createElement("button");
        button.classList.add("keyboard-button");
        button.textContent = this.getKeyDisplayText(key);
        button.dataset.key = key;

        if (key.startsWith("{") && key.endsWith("}")) {
          button.classList.add("special-key");
          if (key === "{space}") button.classList.add("space-key");
          if (key === "{bksp}") button.classList.add("backspace-key");
          if (key === "{enter}") button.classList.add("enter-key");
          if (key === "{shift}") {
            button.classList.add("shift-key");
            if (this.isShift) button.classList.add("active");
          }
          if (key === "{capslock}") {
            button.classList.add("capslock-key");
            if (this.isCapsLock) button.classList.add("active");
          }
          if (key === "{tab}") button.classList.add("tab-key");
          if (key === "{closeKeyboard}")
            button.classList.add("close-keyboard-key");
          if (key === "{toNum}")
            button.classList.add("toggle-layout-key", "numeric-layout-toggle");
          if (key === "{toABC}")
            button.classList.add("toggle-layout-key", "alpha-layout-toggle");
        }

        button.addEventListener("click", this.handleButtonClick);
        rowDiv.appendChild(button);
      });
      this.keyboardContainer.appendChild(rowDiv);
    });
  }

  getKeyDisplayText(key) {
    switch (key) {
      case "{bksp}":
        return "⌫";
      case "{tab}":
        return "Tab";
      case "{enter}":
        return "↵";
      case "{shift}":
        return "Shift ⇧";
      case "{capslock}":
        return "CAPS";
      case "{space}":
        return "Space";
      case "{closeKeyboard}":
        return "Tutup";
      case "{toNum}":
        return "123";
      case "{toABC}":
        return "ABC";
      default:
        return key;
    }
  }

  attachGlobalInputListeners() {
    document.addEventListener("focusin", (event) => {
      if (
        event.target.matches(this.inputSelector) &&
        !event.target.classList.contains("select2-search__field")
      ) {
        this.handleFocus(event);
      }
    });

    document.addEventListener("focusout", (event) => {
      if (
        event.target.matches(this.inputSelector) &&
        !event.target.classList.contains("select2-search__field")
      ) {
        this.handleBlur(event);
      }
    });
    console.log("CustomVirtualKeyboard: Global input listeners attached.");
  }

  attachSelect2GlobalListeners() {
    if (typeof jQuery === "undefined" || typeof $.fn.select2 === "undefined") {
      console.warn(
        "CustomVirtualKeyboard: jQuery or Select2 not found. Select2 integration will not work."
      );
      return;
    }
    $(document).off("select2:open", this.onSelect2OpenGlobal);
    $(document).off("select2:close", this.onSelect2CloseGlobal);

    $(document).on("select2:open", this.onSelect2OpenGlobal);
    $(document).on("select2:close", this.onSelect2CloseGlobal);
    console.log("CustomVirtualKeyboard: Global Select2 listeners attached.");
  }

  attachViewportResizeListener() {
    if (window.visualViewport) {
      window.visualViewport.removeEventListener(
        "resize",
        this.handleVisualViewportResize
      );
      window.visualViewport.addEventListener(
        "resize",
        this.handleVisualViewportResize
      );
      console.log(
        "CustomVirtualKeyboard: VisualViewport resize listener attached."
      );
    } else {
      console.warn(
        "CustomVirtualKeyboard: visualViewport API not supported. Native keyboard detection may be less reliable."
      );
    }
  }

  handleVisualViewportResize() {
    if (!this.isMobileDevice) return;

    const currentViewportHeight = window.visualViewport.height;
    const previousNativeKeyboardState = this.isNativeKeyboardOpen;
    const heightDifference = this.lastViewportHeight - currentViewportHeight;
    const keyboardOpenThreshold = window.screen.height * 0.2; // Adjust as needed, e.g., 20% of screen height

    if (heightDifference > keyboardOpenThreshold) {
      this.isNativeKeyboardOpen = true;
      if (!previousNativeKeyboardState) {
        console.log(
          `CustomVirtualKeyboard: Native keyboard detected open (viewport: ${currentViewportHeight}, prev: ${this.lastViewportHeight}). Hiding custom keyboard.`
        );
        this.hideKeyboard();
      }
    } else if (
      currentViewportHeight >
      this.lastViewportHeight + keyboardOpenThreshold
    ) {
      this.isNativeKeyboardOpen = false;
      if (previousNativeKeyboardState) {
        console.log(
          `CustomVirtualKeyboard: Native keyboard detected closed (viewport: ${currentViewportHeight}, prev: ${this.lastViewportHeight}).`
        );
        if (this.activeInput && document.activeElement === this.activeInput) {
          this.showKeyboard(this.getLayoutForInput(this.activeInput));
        }
      }
    }
    this.lastViewportHeight = currentViewportHeight;
  }

  handleFocus(event) {
    const input = event.target;
    if (input.readOnly || input.disabled) {
      console.log(
        "CustomVirtualKeyboard: Input is readonly or disabled, not showing keyboard.",
        input.id || input.name
      );
      return;
    }

    setTimeout(() => {
      this.handleVisualViewportResize();
      if (!this.isNativeKeyboardOpen) {
        this.activeInput = input;
        this.showKeyboard(this.getLayoutForInput(input));
        console.log(
          "CustomVirtualKeyboard: Input focused, showing custom keyboard."
        );
      } else {
        console.log(
          "CustomVirtualKeyboard: Input focused, but native keyboard is open. Not showing custom keyboard."
        );
        this.hideKeyboard();
        this.activeInput = null;
      }
    }, 150);
  }

  handleBlur() {
    setTimeout(() => {
      const focusedElement = document.activeElement;
      if (
        this.keyboardContainer.contains(focusedElement) ||
        focusedElement.classList.contains("select2-search__field") ||
        this.isClickingKeyboard // Keep this check to prevent immediate hide on keyboard button click
      ) {
        console.log(
          "CustomVirtualKeyboard: Ignoring blur as focus moved within keyboard/select2 or keyboard click detected."
        );
        return;
      }

      if (this.isNativeKeyboardOpen) {
        console.log(
          "CustomVirtualKeyboard: Blur detected, but native keyboard is open. Keeping custom keyboard hidden."
        );
        return;
      }

      this.hideKeyboard();
      this.activeInput = null;
      console.log("CustomVirtualKeyboard: Input blurred, hiding keyboard.");
    }, 100);
  }

  handleKeyboardMousedown(e) {
    this.isClickingKeyboard = true;
    e.preventDefault(); // Crucial: Prevent default to stop the input from blurring
    e.stopPropagation();
    console.log(
      "CustomVirtualKeyboard: Mousedown on keyboard, isClickingKeyboard = true"
    );
  }

  handleKeyboardMouseup() {
    setTimeout(() => {
      this.isClickingKeyboard = false;
      console.log(
        "CustomVirtualKeyboard: Mouseup on keyboard, isClickingKeyboard = false"
      );

      // Refocus the active input if it's still intended to be active and
      // focus isn't already there or taken by native keyboard.
      // This is particularly important for textareas after an Enter key press.
      if (
        this.activeInput &&
        (this.activeInput.classList.contains("select2-search__field") ||
          (!this.activeInput.readOnly && !this.activeInput.disabled)) &&
        document.activeElement !== this.activeInput &&
        !this.isNativeKeyboardOpen
      ) {
        this.activeInput.focus();
        // Manually set cursor to end for better UX, or where it was if selection existed
        if (this.activeInput.setSelectionRange) {
          const len = this.activeInput.value.length;
          this.activeInput.setSelectionRange(len, len);
        }
        console.log(
          "CustomVirtualKeyboard: Refocused active input after keyboard click."
        );
      }
    }, 50);
  }

  handleButtonClick(event) {
    const button = event.target;
    const key = button.dataset.key;

    if (!this.activeInput) {
      console.warn("CustomVirtualKeyboard: No active input to type into.");
      return;
    }

    if (key === "{toNum}" || key === "{toABC}") {
      console.log(
        `CustomVirtualKeyboard: Toggling layout to ${
          key === "{toNum}" ? "number" : "default"
        }.`
      );
      this.renderKeyboard(key === "{toNum}" ? "number" : "default");
      if (this.activeInput) {
        this.activeInput.focus();
      }
      return;
    }

    if (key === "{closeKeyboard}") {
      this.hideKeyboard();
      if (this.activeInput) this.activeInput.blur();
      return;
    }

    let inputValue = this.activeInput.value;
    let selectionStart = this.activeInput.selectionStart;
    let selectionEnd = this.activeInput.selectionEnd;

    switch (key) {
      case "{bksp}":
        if (selectionStart > 0 && selectionStart === selectionEnd) {
          inputValue =
            inputValue.substring(0, selectionStart - 1) +
            inputValue.substring(selectionEnd);
          selectionStart = Math.max(0, selectionStart - 1);
        } else if (selectionStart !== selectionEnd) {
          inputValue =
            inputValue.substring(0, selectionStart) +
            inputValue.substring(selectionEnd);
          selectionEnd = selectionStart;
        }
        break;
      case "{space}":
        inputValue =
          inputValue.substring(0, selectionStart) +
          " " +
          inputValue.substring(selectionEnd);
        selectionStart++;
        break;
      case "{enter}":
        if (this.activeInput.tagName === "TEXTAREA") {
          inputValue =
            inputValue.substring(0, selectionStart) +
            "\n" +
            inputValue.substring(selectionEnd);
          selectionStart++;
        }
        // Dispatch custom event for both input and textarea on Enter
        const virtualEnterEvent = new CustomEvent("virtualkeyboard:enter", {
          bubbles: true,
          composed: true,
          detail: {
            value: inputValue, // Pass the potentially updated value
            inputId: this.activeInput.id,
            inputElement: this.activeInput, // Pass the element itself
          },
        });
        this.activeInput.dispatchEvent(virtualEnterEvent);

        // Update the active input's value after dispatching the event
        this.activeInput.value = inputValue;
        this.activeInput.setSelectionRange(selectionStart, selectionStart);

        // Dispatch 'input' event to ensure reactivity frameworks update
        const inputEvent = new Event("input", { bubbles: true });
        this.activeInput.dispatchEvent(inputEvent);

        // Crucial: After an Enter key, we want to ensure the input *remains focused*
        // and the keyboard stays visible, unless the application's handler for
        // 'virtualkeyboard:enter' explicitly changes focus or hides the keyboard.
        // We'll rely on the handleKeyboardMouseup's setTimeout to re-focus.
        console.log(
          "CustomVirtualKeyboard: Dispatched 'virtualkeyboard:enter' event. Maintaining focus."
        );
        return; // Important: Return immediately after handling enter
      case "{shift}":
        this.isShift = !this.isShift;
        this.renderKeyboard(this.isShift ? "shift" : "default");
        return;
      case "{capslock}":
        this.isCapsLock = !this.isCapsLock;
        this.renderKeyboard(
          this.isCapsLock ? (this.isShift ? "shift" : "default") : "default"
        );
        return;
      case "{tab}":
        console.log(
          "Tab pressed. Implementing custom tab logic here (e.g., move focus to next input)."
        );
        const focusableElements = Array.from(
          document.querySelectorAll(
            'input, select, textarea, button, [tabindex]:not([tabindex="-1"])'
          )
        ).filter((el) => !el.disabled && !el.readOnly);
        const currentIndex = focusableElements.indexOf(this.activeInput);
        if (
          currentIndex !== -1 &&
          currentIndex + 1 < focusableElements.length
        ) {
          focusableElements[currentIndex + 1].focus();
        } else {
          this.hideKeyboard();
        }
        return;
      default:
        let charToInsert = key;
        if (this.isShift && this.layouts.shift) {
          charToInsert = key.toUpperCase();
        } else if (
          this.isCapsLock &&
          key.length === 1 &&
          key.match(/[a-zA-Z]/)
        ) {
          charToInsert = key.toUpperCase();
        } else if (
          this.isCapsLock &&
          this.isShift &&
          key.length === 1 &&
          key.match(/[a-zA-Z]/)
        ) {
          charToInsert = key.toLowerCase();
        }

        inputValue =
          inputValue.substring(0, selectionStart) +
          charToInsert +
          inputValue.substring(selectionEnd);
        selectionStart += charToInsert.length;

        if (this.isShift && !this.isCapsLock) {
          this.isShift = false;
          this.renderKeyboard("default");
        }
        break;
    }

    this.activeInput.value = inputValue;
    this.activeInput.setSelectionRange(selectionStart, selectionStart);

    const inputEvent = new Event("input", { bubbles: true });
    this.activeInput.dispatchEvent(inputEvent);

    // After typing, ensure focus remains on the input
    if (this.activeInput && document.activeElement !== this.activeInput) {
      this.activeInput.focus();
    }
  }

  getLayoutForInput(inputElement) {
    if (!inputElement) {
      return "default";
    }
    if (inputElement.type === "number" || inputElement.type === "tel") {
      return "number";
    }
    if (
      inputElement.inputmode === "numeric" ||
      inputElement.inputmode === "decimal"
    ) {
      return "number";
    }
    const pattern = inputElement.getAttribute("pattern");
    if (pattern && (pattern === "[0-9]*" || pattern.includes("\\d+"))) {
      return "number";
    }
    return "default";
  }

  showKeyboard(layoutName = "default") {
    if (!this.isMobileDevice) {
      console.log(
        "CustomVirtualKeyboard: Not a mobile device, skipping show custom keyboard."
      );
      return;
    }
    if (!this.keyboardContainer) {
      console.error(
        "CustomVirtualKeyboard: Keyboard container not initialized."
      );
      return;
    }
    if (this.isNativeKeyboardOpen) {
      console.log(
        "CustomVirtualKeyboard: Native keyboard is open, skipping show custom keyboard."
      );
      return;
    }

    if (!this.layouts[layoutName]) {
      console.warn(
        `CustomVirtualKeyboard: Layout "${layoutName}" not found. Falling back to 'default'.`
      );
      layoutName = "default";
    }

    this.renderKeyboard(layoutName);
    this.keyboardContainer.style.display = "block";
    requestAnimationFrame(() => {
      this.keyboardContainer.classList.add("show");
    });
    console.log("CustomVirtualKeyboard: Keyboard shown.");
  }

  hideKeyboard() {
    if (this.keyboardContainer) {
      this.keyboardContainer.classList.remove("show");
      const handler = () => {
        this.keyboardContainer.style.display = "none";
        this.keyboardContainer.removeEventListener("transitionend", handler);
      };
      this.keyboardContainer.addEventListener("transitionend", handler, {
        once: true,
      });
      setTimeout(() => {
        if (this.keyboardContainer.classList.contains("show")) {
          this.keyboardContainer.style.display = "none";
          this.keyboardContainer.classList.remove("show");
        }
      }, 300);
    }
    this.activeInput = null;
    this.isShift = false;
    this.isCapsLock = false;
    this.renderKeyboard("default");
    console.log("CustomVirtualKeyboard: Keyboard hidden.");
  }

  restoreSelect2Close(selectElement) {
    const $select2Container = $(selectElement).data("select2");
    if (
      $select2Container &&
      $select2Container.dropdown &&
      this.originalSelect2Close &&
      $select2Container.dropdown.close !== this.originalSelect2Close
    ) {
      $select2Container.dropdown.close = this.originalSelect2Close;
      this.originalSelect2Close = null;
      console.log("CustomVirtualKeyboard: Select2 close function restored.");
    } else if (this.originalSelect2Close) {
      this.originalSelect2Close = null;
    }
  }

  onSelect2OpenGlobal(event) {
    if (!this.isMobileDevice) {
      console.log(
        "CustomVirtualKeyboard: Not a mobile device, skipping Select2 keyboard on open."
      );
      return;
    }

    const selectElement = event.target;
    console.log(
      "CustomVirtualKeyboard: select2:open caught for:",
      selectElement.id || selectElement.name || selectElement.tagName
    );

    this.handleVisualViewportResize();

    setTimeout(() => {
      const searchInput = document.querySelector(this.select2SearchSelector);
      if (searchInput) {
        this.activeInput = searchInput;
        this.activeInput.focus();

        if (this.isNativeKeyboardOpen) {
          console.log(
            "CustomVirtualKeyboard: Select2 opened, but native keyboard is open. Not showing custom keyboard."
          );
          this.hideKeyboard();
        } else {
          this.showKeyboard(this.getLayoutForInput(searchInput));
        }

        const $select2Container = $(selectElement).data("select2");
        if ($select2Container && $select2Container.dropdown) {
          if (!this.originalSelect2Close) {
            this.originalSelect2Close = $select2Container.dropdown.close;
          }
          const self = this;
          $select2Container.dropdown.close = function () {
            if (self.isClickingKeyboard) {
              console.log(
                "CustomVirtualKeyboard: Preventing Select2 close due to keyboard click."
              );
              return;
            }
            if (self.originalSelect2Close) {
              self.originalSelect2Close.apply(this, arguments);
            }
          };
        }
      } else {
        console.warn(
          "CustomVirtualKeyboard: Select2 search input not found after open event."
        );
      }
    }, 100);
  }

  onSelect2CloseGlobal(event) {
    if (!this.isMobileDevice) {
      console.log(
        "CustomVirtualKeyboard: Not a mobile device, skipping Select2 keyboard on close."
      );
      return;
    }

    const selectElement = event.target;
    console.log(
      "CustomVirtualKeyboard: select2:close caught for:",
      selectElement.id || selectElement.name || selectElement.tagName
    );

    this.restoreSelect2Close(selectElement);

    if (!this.isClickingKeyboard) {
      this.hideKeyboard();
      this.activeInput = null;
      console.log(
        "CustomVirtualKeyboard: Select2 closed, hiding custom keyboard."
      );
    } else {
      console.log(
        "CustomVirtualKeyboard: Select2 closed, but not hiding custom keyboard due to keyboard click flag."
      );
    }
  }
}

document.addEventListener("DOMContentLoaded", () => {
  if (!window.customVirtualKeyboard) {
    window.customVirtualKeyboard = new CustomVirtualKeyboard();
  }
});
