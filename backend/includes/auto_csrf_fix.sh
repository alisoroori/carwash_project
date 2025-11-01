#!/bin/bash
# ===========================================
# Auto CSRF Token Fix for CarWash Project
# ===========================================

HEADER_FILE="./header.php"

if [ ! -f "$HEADER_FILE" ]; then
    echo "❌ header.php not found at $HEADER_FILE"
    exit 1
fi

# Step 1: Check if CSRF meta already exists
if grep -q 'meta name="csrf-token"' "$HEADER_FILE"; then
    echo "✅ CSRF meta token already exists in header.php"
else
    echo "⚙️ Adding CSRF token to header.php..."

    # Step 2: Prepare CSRF block
    read -r -d '' CSRF_BLOCK <<'EOB'
<?php
session_start();
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<meta name="csrf-token" content="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
<script>
window.CONFIG = window.CONFIG || {};
window.CONFIG.CSRF_TOKEN = "<?= htmlspecialchars($_SESSION['csrf_token']) ?>";
</script>
EOB

    # Step 3: Insert before </head> if exists, else prepend
    if grep -q "</head>" "$HEADER_FILE"; then
        sed -i "/<\/head>/i $CSRF_BLOCK" "$HEADER_FILE"
    else
        echo -e "$CSRF_BLOCK\n$(cat $HEADER_FILE)" > "$HEADER_FILE"
    fi

    echo "✅ CSRF token block added successfully."
fi

# Step 4: Instructions for JS
echo "ℹ️ Next step: In your JS fetch or AJAX requests, include X-CSRF-TOKEN header:"
echo "'X-CSRF-TOKEN': window.CONFIG?.CSRF_TOKEN || document.querySelector('meta[name=\"csrf-token\"]')?.content"
echo "✅ CSRF token is now ready for use in POST/PUT requests!"
