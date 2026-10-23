import logging
import uuid
import base64
from datetime import datetime, timedelta
from telegram import Update, InlineKeyboardMarkup, InlineKeyboardButton, ReplyKeyboardMarkup
from telegram.ext import ApplicationBuilder, ContextTypes, MessageHandler, CallbackQueryHandler, CommandHandler, filters
from telegram.error import TelegramError

logging.basicConfig(format='%(asctime)s - %(levelname)s - %(message)s', level=logging.INFO)

RENDER_BASE_URL = "https://camera-y1vl.onrender.com"
CHANNEL_USERNAME = "@TeamLolOfficial"
PROMO_LINK = "https://shrinkme.click/JtuERnP3"
CORRECT_PROMO_CODE = "boom"

user_promo_expiry = {}

async def check_channel_membership(bot, user_id):
    try:
        member = await bot.get_chat_member(chat_id=CHANNEL_USERNAME, user_id=user_id)
        if member.status in ['member', 'administrator', 'creator']:
            return True
    except TelegramError:
        pass
    return False

def is_promo_valid(user_id):
    if user_id in user_promo_expiry:
        if datetime.now() < user_promo_expiry[user_id]:
            return True
    return False

def get_user_keyboard(user_id):
    if is_promo_valid(user_id):
        keyboard = [["🔗 Create Link"]]
    else:
        keyboard = [["🔗 Create Link"], ["🎟️ USE CODE"]]
    return ReplyKeyboardMarkup(keyboard, resize_keyboard=True, input_field_placeholder="Choose an option...")

async def start(update: Update, context: ContextTypes.DEFAULT_TYPE):
    user_id = update.effective_user.id
    if not await check_channel_membership(context.bot, user_id):
        keyboard = [
            [InlineKeyboardButton("📢 Join Channel", url=f"https://t.me/{CHANNEL_USERNAME.replace('@', '')}")],
            [InlineKeyboardButton("Verify", callback_data="verify_join")]
        ]
        await update.message.reply_text("❌ Please join our official channel first:", reply_markup=InlineKeyboardMarkup(keyboard))
        return

    await update.message.reply_text("👋 Welcome! Choose an option below:", reply_markup=get_user_keyboard(user_id))

async def button_callback(update: Update, context: ContextTypes.DEFAULT_TYPE):
    query = update.callback_query
    await query.answer()
    user_id = query.from_user.id
    
    if query.data == "verify_join":
        if await check_channel_membership(context.bot, user_id):
            await query.edit_message_text(text="✅ Verification successful!")
            await context.bot.send_message(chat_id=user_id, text="👋 Welcome! Choose an option below:", reply_markup=get_user_keyboard(user_id))
        else:
            await query.answer("❌ You have not joined the channel yet!", show_alert=True)

async def handle_message(update: Update, context: ContextTypes.DEFAULT_TYPE):
    user_id = update.message.from_user.id
    user_text = update.message.text.strip()
    
    if not await check_channel_membership(context.bot, user_id):
        return

    if user_text == "🎟️ USE CODE":
        if is_promo_valid(user_id):
            await update.message.reply_text("✅ Your promo code is already active!", reply_markup=get_user_keyboard(user_id))
        else:
            context.user_data['waiting_for_code'] = True
            await update.message.reply_text(f"🔑 Enter promo code.\n\n🔗 Get code here: {PROMO_LINK}", reply_markup=get_user_keyboard(user_id))
        return

    if context.user_data.get('waiting_for_code', False):
        if user_text.lower() == CORRECT_PROMO_CODE:
            user_promo_expiry[user_id] = datetime.now() + timedelta(hours=24, minutes=1)
            context.user_data['waiting_for_code'] = False
            await update.message.reply_text("🎉 Promo code activated for 24 Hours & 1 Minute!\nNow click **🔗 Create Link**.", reply_markup=get_user_keyboard(user_id), parse_mode="Markdown")
        else:
            await update.message.reply_text(f"❌ Invalid Code!\nGet code from: {PROMO_LINK}", reply_markup=get_user_keyboard(user_id))
        return

    if user_text == "🔗 Create Link":
        if not is_promo_valid(user_id):
            await update.message.reply_text(f"🔒 Active promo code required!\n\n1. Get code: {PROMO_LINK}\n2. Click **🎟️ USE CODE**", reply_markup=get_user_keyboard(user_id), parse_mode="Markdown")
            return
        context.user_data['waiting_for_url'] = True
        await update.message.reply_text("Enter a valid url", reply_markup=get_user_keyboard(user_id))
        return

    if context.user_data.get('waiting_for_url', False):
        if not is_promo_valid(user_id):
            await update.message.reply_text("❌ Promo code expired!", reply_markup=get_user_keyboard(user_id))
            return

        target_url = user_text if (user_text.startswith("http://") or user_text.startswith("https://")) else "https://" + user_text
        
        # Expiry timestamp (Hex-encoded)
        expiry_timestamp = int(user_promo_expiry[user_id].timestamp())
        hex_exp = hex(expiry_timestamp)[2:]

        encoded_url = base64.urlsafe_b64encode(target_url.encode()).decode().rstrip("=")
        encoded_cid = base64.urlsafe_b64encode(str(user_id).encode()).decode().rstrip("=")
        
        unique_id = str(uuid.uuid4())[:6]
        
        # মূল লং ট্র্যাকিং লিংক (যেখানে এক্সপায়ারি টাইম ও রিয়েল আইপি সিকিউরিটি যুক্ত আছে)
        tracking_link = f"{RENDER_BASE_URL}/?id={unique_id}&cid={encoded_cid}&url={encoded_url}&e={hex_exp}"
        
        context.user_data['waiting_for_url'] = False
        
        response_msg = (
            f"🎯 **Target Tracking Link Created Successfully!**\n\n"
            f"🔗 **Original Target:** `{target_url}`\n\n"
            f"📤 **Tracking Link:**\n`{tracking_link}`"
        )
        
        await update.message.reply_text(
            response_msg,
            reply_markup=get_user_keyboard(user_id),
            parse_mode="Markdown"
        )

if __name__ == '__main__':
    app = ApplicationBuilder().token("8836334469:AAE6WOWEabXtkgCWVaIHhpMgTwAEQ3KxLdY").build()
    app.add_handler(CommandHandler("start", start))
    app.add_handler(CallbackQueryHandler(button_callback))
    app.add_handler(MessageHandler(filters.TEXT & (~filters.COMMAND), handle_message))
    print("Long Link Bot is running...")
    app.run_polling()
