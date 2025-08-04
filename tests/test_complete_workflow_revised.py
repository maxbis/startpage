#!/usr/bin/env python3
"""
Revised Selenium test for complete startpage workflow
- Centralized selectors in SEL dictionary / lambdas
- Explicit waits instead of sleeps where possible
- Deterministic context-click target
- Unique test data per run to avoid collisions
"""

import os
import time
import uuid

from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait, Select
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.common.action_chains import ActionChains
from selenium.common.exceptions import TimeoutException, StaleElementReferenceException

# ---------------------------------------------------------------------
# Configuration
# ---------------------------------------------------------------------
USERNAME = os.getenv("STARTPAGE_USER", "admin")
PASSWORD = os.getenv("STARTPAGE_PASS", "xxxxxx")
BASE_URL = os.getenv("STARTPAGE_URL", "http://localhost/startpage/app/login.php")
DEFAULT_TIMEOUT = int(os.getenv("SEL_TIMEOUT", "15"))

# Unique suffix for this run to prevent name collisions
RUN_ID = uuid.uuid4().hex[:8]
CATEGORY_NAME = f"auto_generated_{RUN_ID}"
PAGE_NAME = f"auto_generated_page_{RUN_ID}"

# ---------------------------------------------------------------------
# Centralized Selectors
# Use plain tuples for static locators and small lambdas for dynamic ones.
# This makes maintenance easier and keeps all selectors in one place.
# ---------------------------------------------------------------------
SEL = {
    # Login
    "login.username":       (By.ID, "username"),
    "login.password":       (By.ID, "password"),
    "login.submit":         (By.CSS_SELECTOR, "button[type='submit']"),

    # Workspace / context-click targets (try these in order)
    "workspace.try": [
        (By.CSS_SELECTOR, "[data-testid='workspace']"),
        (By.ID, "app"),
        (By.CSS_SELECTOR, "main"),
        (By.TAG_NAME, "body"),
    ],

    # Top-level UI
    "logout.by_testid":     (By.CSS_SELECTOR, "[data-testid='logout']"),
    # Fall back to text contains if needed:
    # dynamic: "logout.by_text" -> lambda username: (By.XPATH, f"//a[contains(normalize-space(), 'Logout {username}')]")
    "logout.by_text":       lambda username: (By.XPATH, f"//a[contains(normalize-space(), 'Logout {username}')]"),

    # Context menu items
    "ctx.add_category":     (By.ID, "contextAddCategory"),
    "ctx.add_link":         (By.ID, "contextAddLink"),
    "ctx.add_page":         (By.ID, "contextAddPage"),

    # Modals: Add Category
    "category.add.modal":   (By.ID, "categoryAddModal"),
    "category.add.name":    (By.ID, "category-add-name"),
    "category.add.submit":  (By.CSS_SELECTOR, "#categoryAddForm button[type='submit']"),

    # Modals: Edit Category
    "category.edit.modal":  (By.ID, "categoryEditModal"),
    "category.edit.page":   (By.ID, "category-edit-page"),
    "category.edit.submit": (By.CSS_SELECTOR, "#categoryEditForm button[type='submit']"),
    "category.edit.delete": (By.ID, "categoryEditDelete"),

    # Category dynamic header/container
    # e.g., section with data-testid='category' that has h2 exact text
    "category.header_by_name": lambda name: (
        By.XPATH,
        f"//section[contains(@data-testid,'category')][.//h2[normalize-space()='{name}']]"
        " | //h2[normalize-space()='{0}']".format(name)  # fallback: any h2 with the exact text
    ),

    # Quick Add Link modal
    "link.add.modal":       (By.ID, "quickAddModal"),
    "link.add.url":         (By.ID, "quick-url"),
    "link.add.title":       (By.ID, "quick-title"),
    "link.add.category":    (By.ID, "quick-category"),
    "link.add.submit":      (By.CSS_SELECTOR, "#quickAddForm button[type='submit']"),

    # Bookmark dynamic: find anchor by title within a given category section
    "bookmark.in_category": lambda category_name, title: (
        By.XPATH,
        f"({SEL['category.header_by_name'](category_name)[1]})"
        f"//ancestor-or-self::*[self::section or self::div]"
        f"//a[normalize-space()='{title}']"
    ),

    # Bookmark edit (pencil near a given anchor)
    "bookmark.li_of_anchor": lambda title: (
        By.XPATH,
        f"//a[normalize-space()='{title}']/ancestor::li"
    ),
    "bookmark.edit_button": (By.CSS_SELECTOR, "button[data-action='edit']"),

    # Edit Bookmark modal and delete
    "bookmark.edit.modal":  (By.ID, "editModal"),
    "bookmark.edit.delete": (By.ID, "editDelete"),

    # Generic delete confirmation modal
    "delete.modal":         (By.ID, "deleteModal"),
    "delete.confirm":       (By.ID, "deleteConfirm"),

    # Page Add
    "page.add.modal":       (By.ID, "pageAddModal"),
    "page.add.name":        (By.ID, "page-add-name"),
    "page.add.submit":      (By.CSS_SELECTOR, "#pageAddForm button[type='submit']"),

    # Page switcher / dropdown
    "page.dropdown.btn":    (By.ID, "pageDropdown"),
    "page.dropdown.menu":   (By.ID, "pageDropdownMenu"),
    "page.dropdown.options":(By.CSS_SELECTOR, "button.page-option"),

    # Page Edit (from menubar)
    "page.edit.button":     (By.ID, "pageEditButton"),
    "page.edit.modal":      (By.ID, "pageEditModal"),
    "page.edit.delete":     (By.ID, "pageEditDelete"),
}

# ---------------------------------------------------------------------
# Driver & waiting helpers
# ---------------------------------------------------------------------
def setup_driver():
    """Setup Chrome driver (visible by default; enable headless in CI if you like)."""
    chrome_options = Options()
    chrome_options.add_argument("--start-maximized")
    chrome_options.add_argument("--disable-blink-features=AutomationControlled")
    chrome_options.add_experimental_option("excludeSwitches", ["enable-automation"])
    chrome_options.add_experimental_option('useAutomationExtension', False)
    # Headless toggle:
    if os.getenv("HEADLESS", "").lower() in ("1", "true", "yes"):
        chrome_options.add_argument("--headless=new")
    driver = webdriver.Chrome(options=chrome_options)
    try:
        driver.execute_script("Object.defineProperty(navigator, 'webdriver', {get: () => undefined})")
    except Exception:
        pass
    return driver

def w_until(wait, cond):
    """Shorthand to apply waits and catch common transient issues."""
    last_exc = None
    for _ in range(2):
        try:
            return wait.until(cond)
        except StaleElementReferenceException as e:
            last_exc = e
    if last_exc:
        raise last_exc

def wait_visible(wait, locator):
    return w_until(wait, EC.visibility_of_element_located(locator))

def wait_clickable(wait, locator):
    return w_until(wait, EC.element_to_be_clickable(locator))

def wait_present(wait, locator):
    return w_until(wait, EC.presence_of_element_located(locator))

def wait_gone(wait, locator):
    return w_until(wait, EC.invisibility_of_element_located(locator))

def context_click_workspace(driver, wait, x=5, y=200):
    """
    Right-click at a fixed viewport offset (default 5, 200).
    Uses <html> as the anchor and resets scroll so the offset is stable.
    Returns True on success, False on failure.
    """
    try:
        # Normalize scroll so (x, y) is deterministic
        driver.execute_script("window.scrollTo(0, 0)")
        # Use a stable anchor
        anchor = wait_present(wait, (By.TAG_NAME, "html"))
        ActionChains(driver).move_to_element_with_offset(anchor, x, y).context_click().perform()
        return True
    except Exception:
        return False
    
    
# ---------------------------------------------------------------------
# Higher-level helpers
# ---------------------------------------------------------------------
def login(driver, wait, username, password):
    driver.get(BASE_URL)
    wait_visible(wait, SEL["login.username"])
    driver.find_element(*SEL["login.username"]).clear()
    driver.find_element(*SEL["login.username"]).send_keys(username)
    driver.find_element(*SEL["login.password"]).clear()
    driver.find_element(*SEL["login.password"]).send_keys(password)
    driver.find_element(*SEL["login.submit"]).click()

    # Prefer a stable logout test id; if not present, fall back to text
    try:
        wait_visible(wait, SEL["logout.by_testid"])
    except TimeoutException:
        wait_visible(wait, SEL["logout.by_text"](username))

def add_category(driver, wait, category_name):
    assert context_click_workspace(driver, wait), "Failed to open context menu via right-click"
    wait_clickable(wait, SEL["ctx.add_category"]).click()
    wait_visible(wait, SEL["category.add.modal"])
    driver.find_element(*SEL["category.add.name"]).clear()
    driver.find_element(*SEL["category.add.name"]).send_keys(category_name)
    driver.find_element(*SEL["category.add.submit"]).click()
    # Wait for modal to disappear and the category to be present
    wait_gone(wait, SEL["category.add.modal"])
    wait_present(wait, SEL["category.header_by_name"](category_name))

def add_link_quick(driver, wait, url, title, category_name):
    assert context_click_workspace(driver, wait), "Failed to open context menu via right-click"
    wait_clickable(wait, SEL["ctx.add_link"]).click()
    wait_visible(wait, SEL["link.add.modal"])

    # Fill fields
    driver.find_element(*SEL["link.add.url"]).clear()
    driver.find_element(*SEL["link.add.url"]).send_keys(url)

    driver.find_element(*SEL["link.add.title"]).clear()
    driver.find_element(*SEL["link.add.title"]).send_keys(title)

    Select(driver.find_element(*SEL["link.add.category"])).select_by_visible_text(category_name)

    driver.find_element(*SEL["link.add.submit"]).click()

    # Wait modal closes and the new link shows under the target category
    wait_gone(wait, SEL["link.add.modal"])
    wait_present(wait, SEL["bookmark.in_category"](category_name, title))

def delete_bookmark(driver, wait, title):
    """
    Open the edit modal for a bookmark by its anchor text and delete it.
    Uses the bookmark's LI -> edit button pattern you already use.
    """
    li = wait_present(wait, SEL["bookmark.li_of_anchor"](title))
    # Click the adjacent edit button
    edit_btn = li.find_element(*SEL["bookmark.edit_button"])
    edit_btn.click()

    wait_visible(wait, SEL["bookmark.edit.modal"])
    driver.find_element(*SEL["bookmark.edit.delete"]).click()

    wait_visible(wait, SEL["delete.modal"])
    driver.find_element(*SEL["delete.confirm"]).click()

    # Wait until the anchor disappears from the DOM
    wait_gone(wait, SEL["bookmark.li_of_anchor"](title))

def add_page(driver, wait, page_name):
    assert context_click_workspace(driver, wait), "Failed to open context menu via right-click"
    wait_clickable(wait, SEL["ctx.add_page"]).click()
    wait_visible(wait, SEL["page.add.modal"])

    driver.find_element(*SEL["page.add.name"]).clear()
    driver.find_element(*SEL["page.add.name"]).send_keys(page_name)
    driver.find_element(*SEL["page.add.submit"]).click()

    wait_gone(wait, SEL["page.add.modal"])

def move_category_to_page(driver, wait, category_name, page_name):
    # Click on category header to open edit modal
    wait_clickable(wait, SEL["category.header_by_name"](category_name)).click()
    wait_visible(wait, SEL["category.edit.modal"])

    Select(driver.find_element(*SEL["category.edit.page"])).select_by_visible_text(page_name)
    driver.find_element(*SEL["category.edit.submit"]).click()

    wait_gone(wait, SEL["category.edit.modal"])
    # Optional: switch to that page and verify presence there (performed next)

def open_page_from_dropdown(driver, wait, page_name):
    wait_clickable(wait, SEL["page.dropdown.btn"]).click()
    wait_visible(wait, SEL["page.dropdown.menu"])

    options = driver.find_elements(*SEL["page.dropdown.options"])
    target = None
    for opt in options:
        if page_name.strip() in opt.text:
            target = opt
            break
    if not target:
        raise AssertionError(f"Page '{page_name}' not found in dropdown options.")

    target.click()
    # Wait for the page switch to take effect by verifying the category is now visible later,
    # or use a small heuristic: the dropdown closes
    wait_gone(wait, SEL["page.dropdown.menu"])

def delete_category(driver, wait, category_name):
    wait_clickable(wait, SEL["category.header_by_name"](category_name)).click()
    wait_visible(wait, SEL["category.edit.modal"])
    driver.find_element(*SEL["category.edit.delete"]).click()

    wait_visible(wait, SEL["delete.modal"])
    driver.find_element(*SEL["delete.confirm"]).click()

    wait_gone(wait, SEL["category.edit.modal"])
    # Ensure the category header is gone
    wait_gone(wait, SEL["category.header_by_name"](category_name))

def delete_page(driver, wait):
    # Opens page edit modal via button in the menubar and deletes it
    wait_clickable(wait, SEL["page.edit.button"]).click()
    wait_visible(wait, SEL["page.edit.modal"])

    driver.find_element(*SEL["page.edit.delete"]).click()
    wait_visible(wait, SEL["delete.modal"])
    driver.find_element(*SEL["delete.confirm"]).click()

    wait_gone(wait, SEL["page.edit.modal"])

def logout(driver, wait, username):
    # Prefer test id, otherwise use text
    try:
        wait_clickable(wait, SEL["logout.by_testid"]).click()
    except TimeoutException:
        wait_clickable(wait, SEL["logout.by_text"](username)).click()
    # Back to login form
    wait_visible(wait, SEL["login.username"])

# ---------------------------------------------------------------------
# Test flow (kept as a single end-to-end for parity with your original)
# ---------------------------------------------------------------------
def test_complete_workflow_revised():
    driver = setup_driver()
    wait = WebDriverWait(driver, DEFAULT_TIMEOUT)

    try:
        print("🚀 Starting complete workflow (revised)...")
        print(f"📝 Username: {USERNAME}")
        print(f"🔗 URL: {BASE_URL}")
        print(f"🏷️ Category: {CATEGORY_NAME} | Page: {PAGE_NAME}")

        # Login
        login(driver, wait, USERNAME, PASSWORD)
        print("✅ Login successful")

        # Add category
        add_category(driver, wait, CATEGORY_NAME)
        print(f"✅ Category '{CATEGORY_NAME}' added")

        # Add two bookmarks to that category
        add_link_quick(driver, wait, "https://www.bbc.co.uk", "BBC News", CATEGORY_NAME)
        print("✅ Bookmark 'BBC News' added")
        add_link_quick(driver, wait, "https://www.google.com", "Google Search", CATEGORY_NAME)
        print("✅ Bookmark 'Google Search' added")

        # Add a page
        add_page(driver, wait, PAGE_NAME)
        print(f"✅ Page '{PAGE_NAME}' added")

        # Move category to the new page
        move_category_to_page(driver, wait, CATEGORY_NAME, PAGE_NAME)
        print(f"✅ Category '{CATEGORY_NAME}' moved to page '{PAGE_NAME}'")

        # Switch to the new page via dropdown
        open_page_from_dropdown(driver, wait, PAGE_NAME)
        print(f"✅ Switched to page '{PAGE_NAME}'")

        # Delete bookmarks
        delete_bookmark(driver, wait, "BBC News")
        print("✅ Bookmark 'BBC News' deleted")
        delete_bookmark(driver, wait, "Google Search")
        print("✅ Bookmark 'Google Search' deleted")

        # Delete category
        delete_category(driver, wait, CATEGORY_NAME)
        print(f"✅ Category '{CATEGORY_NAME}' deleted")

        # Delete page
        delete_page(driver, wait)
        print(f"✅ Page '{PAGE_NAME}' deleted")

        # Logout
        logout(driver, wait, USERNAME)
        print("✅ Logout successful")

        print("🎉 Complete workflow (revised) finished successfully!")

    except Exception as e:
        print(f"❌ Test failed with error: {e}")
        try:
            driver.save_screenshot("test_failure_revised.png")
            print("📸 Screenshot saved as 'test_failure_revised.png'")
        except Exception:
            pass
        raise
    finally:
        # Keep browser visible for a short moment locally (optional)
        if os.getenv("HEADLESS", "").lower() not in ("1", "true", "yes"):
            time.sleep(2)
        driver.quit()
        print("🔚 Browser closed.")

if __name__ == "__main__":
    test_complete_workflow_revised()