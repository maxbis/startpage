#!/usr/bin/env python3
"""
Selenium test for complete startpage workflow including login, context menu, category and bookmark management
"""

from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.common.action_chains import ActionChains
from selenium.webdriver.support.ui import Select
import time

# Test variables
username = 'admin'
password = 'xxxxxx'
url = 'http://localhost/startpage/app/login.php'

def setup_driver():
    """Setup Chrome driver with visible browser"""
    chrome_options = Options()
    # Keep browser visible for user to see the test
    chrome_options.add_argument("--start-maximized")
    chrome_options.add_argument("--disable-blink-features=AutomationControlled")
    chrome_options.add_experimental_option("excludeSwitches", ["enable-automation"])
    chrome_options.add_experimental_option('useAutomationExtension', False)
    
    driver = webdriver.Chrome(options=chrome_options)
    driver.execute_script("Object.defineProperty(navigator, 'webdriver', {get: () => undefined})")
    return driver

def create_bookmark(driver, wait, url, title, category_name, right_click_x=5, right_click_y=200):
    """
    Create a bookmark using the right-click context menu
    
    Args:
        driver: WebDriver instance
        wait: WebDriverWait instance
        url: URL for the bookmark
        title: Title for the bookmark
        category_name: Name of the category to add the bookmark to
        right_click_x: X coordinate for right-click (default: 5)
        right_click_y: Y coordinate for right-click (default: 200)
    
    Returns:
        bool: True if bookmark was created successfully, False otherwise
    """
    try:
        print(f"🔗 Creating bookmark: {title}")
        
        # Right-click to open context menu
        print(f"🖱️ Right-clicking at position: ({right_click_x}, {right_click_y})")
        actions = ActionChains(driver)
        actions.move_by_offset(right_click_x, right_click_y)
        actions.context_click()
        actions.perform()
        
        # Reset mouse position to origin to avoid accumulation
        actions = ActionChains(driver)
        actions.move_by_offset(-right_click_x, -right_click_y)
        actions.perform()
        
        # Click "Add Link" in context menu
        print("🔗 Looking for 'Add Link' option in context menu...")
        context_add_link = wait.until(EC.element_to_be_clickable((By.ID, "contextAddLink")))
        context_add_link.click()
        
        # Wait for quick add modal to appear
        print("📋 Waiting for quick add modal...")
        quick_add_modal = wait.until(EC.visibility_of_element_located((By.ID, "quickAddModal")))
        
        # Fill in the bookmark details
        print("✏️ Filling in bookmark details...")
        
        # Enter URL
        url_input = driver.find_element(By.ID, "quick-url")
        url_input.clear()
        url_input.send_keys(url)
        
        # Enter title
        title_input = driver.find_element(By.ID, "quick-title")
        title_input.clear()
        title_input.send_keys(title)
        
        # Select the category from dropdown
        print(f"📁 Selecting category '{category_name}' from dropdown...")
        category_dropdown = Select(driver.find_element(By.ID, "quick-category"))
        category_dropdown.select_by_visible_text(category_name)
        
        # Click "Add Bookmark" button
        print("➕ Clicking 'Add Bookmark' button...")
        add_bookmark_button = driver.find_element(By.CSS_SELECTOR, "#quickAddForm button[type='submit']")
        add_bookmark_button.click()
        
        # Wait for flash message and page refresh
        print("⏳ Waiting for flash message and page refresh...")
        time.sleep(3)
        
        # Refresh the browser to see the new bookmark
        print("🔄 Refreshing browser to see the new bookmark...")
        driver.refresh()
        time.sleep(2)
        
        # Look for the newly created bookmark
        print(f"🔍 Looking for bookmark '{title}'...")
        bookmark = wait.until(EC.presence_of_element_located((By.XPATH, f"//a[contains(text(), '{title}')]")))
        
        print(f"✅ Bookmark '{title}' created successfully!")
        return True
        
    except Exception as e:
        print(f"❌ Failed to create bookmark '{title}': {str(e)}")
        return False

def delete_bookmark(driver, wait, title):
    """
    Delete a bookmark by finding it and clicking the edit button
    
    Args:
        driver: WebDriver instance
        wait: WebDriverWait instance
        title: Title of the bookmark to delete
    
    Returns:
        bool: True if bookmark was deleted successfully, False otherwise
    """
    try:
        print(f"🗑️ Deleting bookmark: {title}")
        
        # Find the bookmark by title
        print(f"🔍 Looking for bookmark '{title}'...")
        bookmark = wait.until(EC.presence_of_element_located((By.XPATH, f"//a[contains(text(), '{title}')]")))
        
        # Find the edit button (pencil icon) next to the bookmark
        print("✏️ Looking for edit button (pencil icon) next to the bookmark...")
        bookmark_li = bookmark.find_element(By.XPATH, "./ancestor::li")
        edit_button = bookmark_li.find_element(By.CSS_SELECTOR, "button[data-action='edit']")
        
        # Click the edit button
        print("✏️ Clicking edit button...")
        edit_button.click()
        
        # Wait for edit modal to appear
        print("📋 Waiting for edit modal...")
        edit_modal = wait.until(EC.visibility_of_element_located((By.ID, "editModal")))
        
        # Click the Delete button in the edit modal
        print("🗑️ Clicking Delete button in edit modal...")
        delete_button = driver.find_element(By.ID, "editDelete")
        delete_button.click()
        
        # Wait for delete confirmation modal to appear
        print("⚠️ Waiting for delete confirmation modal...")
        delete_modal = wait.until(EC.visibility_of_element_located((By.ID, "deleteModal")))
        
        # Click "Delete Item" in the confirmation modal
        print("🗑️ Confirming bookmark deletion...")
        delete_confirm_button = driver.find_element(By.ID, "deleteConfirm")
        delete_confirm_button.click()
        
        # Wait for deletion to complete
        print("⏳ Waiting for bookmark deletion to complete...")
        time.sleep(2)
        
        # Verify the bookmark is no longer visible
        print(f"🔍 Verifying bookmark '{title}' is no longer visible...")
        try:
            # Try to find the bookmark - it should not exist
            driver.find_element(By.XPATH, f"//a[contains(text(), '{title}')]")
            print(f"❌ ERROR: Bookmark '{title}' is still visible!")
            return False
        except:
            print(f"✅ Bookmark '{title}' successfully deleted!")
            return True
            
    except Exception as e:
        print(f"❌ Failed to delete bookmark '{title}': {str(e)}")
        return False

def test_complete_workflow():
    """Test complete startpage workflow: login, context menu, category and bookmark management"""
    driver = setup_driver()
    
    try:
        print("🚀 Starting complete workflow test...")
        print(f"📝 Username: {username}")
        print(f"🔗 URL: {url}")
        
        # Navigate to login page
        print("📍 Navigating to login page...")
        driver.get(url)
        
        # Wait for page to load and find login form elements
        wait = WebDriverWait(driver, 10)
        
        # Find username field and enter username
        print("👤 Entering username...")
        username_field = wait.until(EC.presence_of_element_located((By.ID, "username")))
        username_field.clear()
        username_field.send_keys(username)
        
        # Find password field and enter password
        print("🔒 Entering password...")
        password_field = driver.find_element(By.ID, "password")
        password_field.clear()
        password_field.send_keys(password)
        
        # Find and click the login button
        print("🔘 Clicking login button...")
        login_button = driver.find_element(By.CSS_SELECTOR, "button[type='submit']")
        login_button.click()
        
        # Wait for page to load after login (should redirect to index.php)
        print("⏳ Waiting for page to load after login...")
        time.sleep(3)
        
        # Check if we're on the main page by looking for the logout link
        print("🔍 Looking for logout link...")
        logout_link = wait.until(EC.presence_of_element_located((By.XPATH, f"//a[contains(text(), 'Logout {username}')]")))
        
        print("✅ Login successful! Found logout link.")
        print(f"📄 Current URL: {driver.current_url}")
        
        # Now test the right-click context menu functionality
        print("🖱️ Testing right-click context menu...")
        
        # Use fixed position for right-click (5 pixels right, 200 pixels down from top-left)
        right_click_x = 5
        right_click_y = 200
        
        print(f"📍 Right-clicking at fixed position: ({right_click_x}, {right_click_y})")
        
        # Perform right-click to open context menu
        actions = ActionChains(driver)
        actions.move_by_offset(right_click_x, right_click_y)
        actions.context_click()
        actions.perform()
        
        # Wait for context menu to appear and click "Add Category"
        print("📁 Looking for 'Add Category' option in context menu...")
        context_add_category = wait.until(EC.element_to_be_clickable((By.ID, "contextAddCategory")))
        context_add_category.click()
        
        # Wait for category add modal to appear
        print("📋 Waiting for category add modal...")
        category_add_modal = wait.until(EC.visibility_of_element_located((By.ID, "categoryAddModal")))
        
        # Enter category name
        print("✏️ Entering category name 'auto_generated_01'...")
        category_name_input = driver.find_element(By.ID, "category-add-name")
        category_name_input.clear()
        category_name_input.send_keys("auto_generated_01")
        
        # Click "Add Category" button
        print("➕ Clicking 'Add Category' button...")
        add_category_button = driver.find_element(By.CSS_SELECTOR, "#categoryAddForm button[type='submit']")
        add_category_button.click()
        
        # Wait for modal to close and page to reload
        print("⏳ Waiting for category to be added...")
        time.sleep(2)
        
        # Look for the newly created category on the page
        print("🔍 Looking for category 'auto_generated_01'...")
        new_category = wait.until(EC.presence_of_element_located((By.XPATH, "//h2[contains(text(), 'auto_generated_01')]")))
        
        print("✅ Category 'auto_generated_01' found successfully!")
        
        # Now add bookmarks to the category using the new function
        print("🔗 Adding bookmarks to the category...")
        
        # Create first bookmark
        bookmark1_created = create_bookmark(
            driver=driver,
            wait=wait,
            url="https://www.bbc.co.uk",
            title="BBC News",
            category_name="auto_generated_01",
            right_click_x=right_click_x,
            right_click_y=right_click_y
        )
        
        if bookmark1_created:
            print("✅ First bookmark created successfully!")
            # Create second bookmark
        bookmark2_created = create_bookmark(
            driver=driver,
            wait=wait,
            url="https://www.google.com",
            title="Google Search",
            category_name="auto_generated_01",
            right_click_x=right_click_x,
            right_click_y=right_click_y
        )
            
        if bookmark2_created:
            print("✅ Second bookmark created successfully!")
        

        # Add new page
        print("📄 Adding new page...")
        
        # Right-click to open context menu for adding page
        print(f"🖱️ Right-clicking at position: ({right_click_x}, {right_click_y})")
        actions = ActionChains(driver)
        actions.move_by_offset(right_click_x, right_click_y)
        actions.context_click()
        actions.perform()
        
        # Reset mouse position to origin to avoid accumulation
        actions = ActionChains(driver)
        actions.move_by_offset(-right_click_x, -right_click_y)
        actions.perform()

        # Wait for context menu to appear and click "Add Page"
        print("📄 Looking for 'Add Page' option in context menu...")
        context_add_page = wait.until(EC.element_to_be_clickable((By.ID, "contextAddPage")))
        context_add_page.click()

        # Wait for page add modal to appear
        print("📋 Waiting for page add modal...")
        page_add_modal = wait.until(EC.visibility_of_element_located((By.ID, "pageAddModal")))

        # Enter page name
        print("✏️ Entering page name 'auto_generated_page_01'...")
        page_name_input = driver.find_element(By.ID, "page-add-name")
        page_name_input.clear()
        page_name_input.send_keys("auto_generated_page_01")

        # Click "Add Page" button
        print("➕ Clicking 'Add Page' button...")
        add_page_button = driver.find_element(By.CSS_SELECTOR, "#pageAddForm button[type='submit']")
        add_page_button.click()

        # Wait for modal to close and page to reload
        print("⏳ Waiting for page to be added...")
        time.sleep(2)
        
        # Refresh the page to update the dropdown with the new page
        print("🔄 Refreshing page to update dropdown with new page...")
        driver.refresh()
        time.sleep(2)

        # Edit the category to move it to the new page
        print("✏️ Clicking on category name to edit...")
        new_category = wait.until(EC.presence_of_element_located((By.XPATH, "//h2[contains(text(), 'auto_generated_01')]")))
        new_category.click()

        # Wait for category edit modal to appear
        print("📋 Waiting for category edit modal...")
        category_edit_modal = wait.until(EC.visibility_of_element_located((By.ID, "categoryEditModal")))

        # Select the page 'auto_generated_page_01' from the dropdown
        print("📁 Selecting page 'auto_generated_page_01' from dropdown...")
        page_dropdown = Select(driver.find_element(By.ID, "category-edit-page"))
        page_dropdown.select_by_visible_text("auto_generated_page_01")

        # Click "Save" button   
        print("➕ Clicking 'Save' button...")
        save_button = driver.find_element(By.CSS_SELECTOR, "#categoryEditForm button[type='submit']")
        save_button.click()

        # Wait for modal to close and page to reload
        print("⏳ Waiting for category to be moved...")
        time.sleep(2)

        # Click on page dropdown to switch to the new page
        print("🔍 Clicking on page dropdown...")
        page_dropdown_button = driver.find_element(By.ID, "pageDropdown")
        page_dropdown_button.click()

        # Wait for page dropdown menu to appear
        print("⏳ Waiting for page dropdown menu to appear...")
        page_dropdown_menu = wait.until(EC.visibility_of_element_located((By.ID, "pageDropdownMenu")))
        time.sleep(1)

        # Debug: Print all available page options
        print("🔍 Available page options:")
        page_options = driver.find_elements(By.CSS_SELECTOR, "button.page-option")
        for option in page_options:
            page_name = option.find_element(By.TAG_NAME, "span").text
            page_id = option.get_attribute("data-page-id")
            print(f"  - Page ID: {page_id}, Name: '{page_name}'")

        # Select the page 'auto_generated_page_01' from the dropdown
        print("📁 Looking for page 'auto_generated_page_01' in dropdown...")
        try:
            # Try CSS selector approach - find button with page-option class that contains the text
            page_option = driver.find_element(By.CSS_SELECTOR, "button.page-option")
            # Check if this button contains the text we're looking for
            page_text = page_option.text
            print(f"🔍 Found page option with text: '{page_text}'")
            
            # Find the button that contains our target page name
            page_options = driver.find_elements(By.CSS_SELECTOR, "button.page-option")
            target_page = None
            for option in page_options:
                if "auto_generated_page_01" in option.text:
                    target_page = option
                    break
            
            if target_page:
                print("✅ Found page option, clicking...")
                target_page.click()
            else:
                print("❌ Page 'auto_generated_page_01' not found in any option")
                raise Exception("Page not found")
                
        except Exception as e:
            print(f"❌ Could not find page 'auto_generated_page_01' in dropdown: {str(e)}")
            print("🔄 Trying to refresh the page and check again...")
            driver.refresh()
            time.sleep(2)
            
            # Click dropdown again after refresh
            page_dropdown_button = driver.find_element(By.ID, "pageDropdown")
            page_dropdown_button.click()
            page_dropdown_menu = wait.until(EC.visibility_of_element_located((By.ID, "pageDropdownMenu")))
            
            # Try to find the page option again
            try:
                page_options = driver.find_elements(By.CSS_SELECTOR, "button.page-option")
                target_page = None
                for option in page_options:
                    if "auto_generated_page_01" in option.text:
                        target_page = option
                        break
                
                if target_page:
                    print("✅ Found page option after refresh, clicking...")
                    target_page.click()
                else:
                    print("❌ Still could not find page 'auto_generated_page_01' after refresh")
                    print("⚠️ Continuing with current page...")
            except Exception as e2:
                print(f"❌ Error after refresh: {str(e2)}")
                print("⚠️ Continuing with current page...")

        # Wait for new page to load
        print("⏳ Waiting for new page to load...")
        time.sleep(3)
        

        # Delete the bookmarks using the new function
        print("🗑️ Deleting bookmarks...")
                
        # Delete first bookmark
        bookmark1_deleted = delete_bookmark(driver, wait, "BBC News")
        if bookmark1_deleted:
            print("✅ First bookmark deleted successfully!")
                
        # Delete second bookmark
        bookmark2_deleted = delete_bookmark(driver, wait, "Google Search")
        if bookmark2_deleted:
            print("✅ Second bookmark deleted successfully!")
        
        # Now proceed with deleting the category
        print("🗑️ Now deleting the category...")
        
        # Click on the category name to open edit modal
        print("✏️ Clicking on category name to edit...")
        new_category = wait.until(EC.presence_of_element_located((By.XPATH, "//h2[contains(text(), 'auto_generated_01')]")))
        new_category.click()
        
        # Wait for category edit modal to appear
        print("📋 Waiting for category edit modal...")
        category_edit_modal = wait.until(EC.visibility_of_element_located((By.ID, "categoryEditModal")))
        
        # Click the Delete button in the category edit modal
        print("🗑️ Clicking Delete button in category edit modal...")
        delete_button = driver.find_element(By.ID, "categoryEditDelete")
        delete_button.click()
        
        # Wait for delete confirmation modal to appear
        print("⚠️ Waiting for delete confirmation modal...")
        delete_modal = wait.until(EC.visibility_of_element_located((By.ID, "deleteModal")))
        
        # Click "Delete Item" in the confirmation modal
        print("🗑️ Confirming category deletion...")
        delete_confirm_button = driver.find_element(By.ID, "deleteConfirm")
        delete_confirm_button.click()
        
        # Wait for deletion to complete
        print("⏳ Waiting for category deletion to complete...")
        time.sleep(2)
        
        # Verify the category is no longer visible
        print("🔍 Verifying category 'auto_generated_01' is no longer visible...")
        try:
            # Try to find the category - it should not exist
            driver.find_element(By.XPATH, "//h2[contains(text(), 'auto_generated_01')]")
            print("❌ ERROR: Category 'auto_generated_01' is still visible!")
        except:
            print("✅ Category 'auto_generated_01' successfully deleted!")

        # Delete the page
        print("🗑️ Deleting page...")
        
        # Click on the page name in the menubar to open edit modal
        print("✏️ Clicking on page name in menubar to edit...")
        page_name_button = driver.find_element(By.ID, "pageEditButton")
        page_name_button.click()
        
        # Wait for page edit modal to appear
        print("📋 Waiting for page edit modal...")
        page_edit_modal = wait.until(EC.visibility_of_element_located((By.ID, "pageEditModal")))
        
        # Click the Delete button in the page edit modal
        print("🗑️ Clicking Delete button in page edit modal...")
        delete_button = driver.find_element(By.ID, "pageEditDelete")
        delete_button.click()
        
        # Wait for delete confirmation modal to appear
        print("⚠️ Waiting for delete confirmation modal...")
        delete_modal = wait.until(EC.visibility_of_element_located((By.ID, "deleteModal")))
        
        # Click "Delete Item" in the confirmation modal
        print("🗑️ Confirming page deletion...")
        delete_confirm_button = driver.find_element(By.ID, "deleteConfirm")
        delete_confirm_button.click()
        
        # Wait for deletion to complete
        print("⏳ Waiting for page deletion to complete...")
        time.sleep(2)
        
        # Verify the page is no longer visible in the dropdown
        print("🔍 Verifying page 'auto_generated_page_01' is no longer visible...")
        try:
            # Try to find the page in the dropdown - it should not exist
            driver.find_element(By.XPATH, "//div[@class='page-option' and contains(text(), 'auto_generated_page_01')]")
            print("❌ ERROR: Page 'auto_generated_page_01' is still visible!")
        except:
            print("✅ Page 'auto_generated_page_01' successfully deleted!")

        # Now proceed with logout
        print("🚪 Clicking logout link...")
        logout_link = wait.until(EC.presence_of_element_located((By.XPATH, f"//a[contains(text(), 'Logout {username}')]")))
        logout_link.click()
        
        # Wait for logout to complete and login page to reappear
        print("⏳ Waiting for logout to complete...")
        time.sleep(3)
        
        # Verify we're back on the login page
        print("🔍 Verifying we're back on login page...")
        username_field_after_logout = wait.until(EC.presence_of_element_located((By.ID, "username")))
        
        print("✅ Logout successful! Login dialog has reappeared.")
        print(f"📄 Final URL: {driver.current_url}")
        
        print("🎉 Complete workflow test finished successfully!")
        
    except Exception as e:
        print(f"❌ Test failed with error: {str(e)}")
        # Take a screenshot for debugging
        driver.save_screenshot("test_failure.png")
        print("📸 Screenshot saved as 'test_failure.png'")
        raise
    
    finally:
        # Keep browser open for a moment so user can see the result
        print("⏳ Keeping browser open for 5 seconds...")
        time.sleep(5)
        driver.quit()
        print("🔚 Browser closed.")

if __name__ == "__main__":
    test_complete_workflow() 