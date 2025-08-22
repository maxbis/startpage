<!-- Password Change Modal -->
<div id="passwordChangeModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 w-full max-w-md mx-4">
        <h3 class="text-lg font-semibold mb-4">🔐 Change Password</h3>
        <form id="passwordChangeForm" class="space-y-4">
            <div>
                <label for="current-password" class="block text-sm font-medium text-gray-700 mb-1">Current Password</label>
                <input type="password" id="current-password" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400" required>
            </div>
            <div>
                <label for="new-password" class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
                <input type="password" id="new-password" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400" required>
            </div>
            <div>
                <label for="confirm-password" class="block text-sm font-medium text-gray-700 mb-1">Confirm New Password</label>
                <input type="password" id="confirm-password" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400" required>
            </div>
            <div class="flex gap-3 pt-4">
                <button type="submit" class="flex-1 bg-blue-500 text-white py-2 px-4 rounded-lg hover:bg-blue-600 transition">Change Password</button>
                <button type="button" id="passwordChangeCancel" class="flex-1 bg-gray-300 text-gray-700 py-2 px-4 rounded-lg hover:bg-gray-400 transition">Cancel</button>
            </div>
        </form>
    </div>
</div>
