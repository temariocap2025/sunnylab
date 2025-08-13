from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.chrome.options import Options
import time
from google import genai
from google.genai import types
import subprocess
import os
import shutil
import pymysql
import json
import tempfile
from datetime import datetime
from pathlib import Path

USERS = ["colegiocapouilliez", "mineducgt", "tn23noticias", "conredgt"]
prompt = 'Este video o imagen habla específicamente sobre un tema ambiental relacionado con Guatemala? o suspensión de clases? (Desastres naturales, radiación UV, calidad del aire, temperatura, humedad, o recomendaciones sobre estos temas). Responde con un 1 o un 0 únicamente (temas prohibidos: Drogas, casos de otros países que no involucran Guatemala)'

connection = pymysql.connect(
    host="localhost", port=3306, user="temario", passwd="temario", database="db_solmaforo"
)

chrome_options = Options()
chrome_options.add_argument("--headless")
chrome_options.add_argument("--disable-blink-features=AutomationControlled")
chrome_options.add_argument("--disable-gpu")
chrome_options.add_argument("--no-sandbox")
chrome_options.add_argument("--disable-dev-shm-usage")
service = Service("/usr/bin/chromedriver")
driver = webdriver.Chrome(service=service, options=chrome_options)

config_path = "/home/temario/.config/gallery-dl/config.json"

with open(f'{Path(__file__).resolve().parents[1]}/apikey.txt') as f:
    api_key = f.read().strip()

os.environ.pop("GOOGLE_API_BASE_URL", None)
os.environ.pop("GOOGLE_API_KEY", None)
client = genai.Client(api_key=api_key)

def refresh_cookie(driver):
    driver.delete_all_cookies()
    driver.get("https://www.instagram.com/")
    driver.add_cookie({
        "name": "sessionid",
        "value": COOKIE,
        "domain": ".instagram.com"
    })
    print("Cookie refreshed.")
    driver.get(driver.current_url)
    time.sleep(5)

def chrome_refresh_cookie():
    temp_profile_dir = tempfile.mkdtemp()
    shutil.copytree(
        '/home/temario/.config/chromium/Default',
        f'{temp_profile_dir}/Default',
        ignore=shutil.ignore_patterns('*.lock'),
        dirs_exist_ok=True, 
        copy_function=shutil.copy2,
        ignore_dangling_symlinks=True)

    chrome_options = Options()
    chrome_options.add_argument("--headless")
    chrome_options.add_argument(f'--user-data-dir={temp_profile_dir}')  
    chrome_options.add_argument('--profile-directory=Default')
    chrome_options.add_argument("--disable-gpu")
    chrome_options.add_argument("--no-sandbox")
    chrome_options.add_argument("--disable-dev-shm-usage")

    chrome_driver = webdriver.Chrome(service=service, options=chrome_options)

    chrome_driver.get("https://www.instagram.com/")
    time.sleep(15)
    cookies = chrome_driver.get_cookies()
    sessionid_cookie = None
    for c in cookies:
        if c['name'] == 'sessionid':
            sessionid_cookie = c['value']
            break

    print(f"SessionID: {sessionid_cookie}")
    update_gallerydl(sessionid_cookie)

    global COOKIE
    COOKIE = sessionid_cookie

    chrome_driver.quit()
    shutil.rmtree(temp_profile_dir)

def update_gallerydl(new_cookie):
    try:
        with open(config_path, "r") as f:
            config_data = json.load(f)
        config_data["extractor"]["instagram"]["cookies"]["sessionid"] = new_cookie
        with open(config_path, "w") as f:
            json.dump(config_data, f, indent=4)
    except Exception as e:
        print("Couldn't update the gallery-dl config file:", e)

def get_pinned_posts(driver):
    pinned_ids = []
    try:
        pinned_icons = driver.find_elements(By.CSS_SELECTOR, 'svg[aria-label="Pinned post icon"]')
        for icon in pinned_icons:
            link = icon.find_element(By.XPATH, "./ancestor::a[1]")
            href = link.get_attribute("href")
            if "/p/" in href:
                shortcode = href.split("/p/")[1].split("/")[0]
                pinned_ids.append(shortcode)
            elif "/reel/" in href:
                shortcode = href.split("/reel/")[1].split("/")[0]
                pinned_ids.append(shortcode)
    except Exception as e:
        print("Error getting pinned posts:", e)
    return pinned_ids

def get_latest_post(driver, pinned_ids):
    posts = driver.find_elements(By.CLASS_NAME, '_aagu')
    post_id = 0
    ext = '.png'
    for post in posts:
        try:
            link = post.find_element(By.XPATH, "./ancestor::a[1]")
            href = link.get_attribute("href")
            if "/p/" in href:
                shortcode = href.split("/p/")[1].split("/")[0]
                if shortcode not in pinned_ids:
                    post_id = shortcode
                    ext = '.png'
                    break
            elif "/reel/" in href:
                shortcode = href.split("/reel/")[1].split("/")[0]
                if shortcode not in pinned_ids:
                    post_id = shortcode
                    ext = '.mp4'
                    break
        except Exception as e:
            print("Error getting post:", e)
    return post_id, ext

COOKIE = ''
chrome_refresh_cookie()
print(f"Monitoring: {USERS}")
while True:
    for USER in USERS:
        try:
            driver.get(f"https://www.instagram.com/{USER}/")
            driver.add_cookie({"name": "sessionid", "value": COOKIE, "domain": ".instagram.com"})
            driver.get(f"https://www.instagram.com/{USER}/")
            time.sleep(5)

            pinned_ids = get_pinned_posts(driver)
            post_id, ext = get_latest_post(driver, pinned_ids)

            if post_id == 0:
                print("Post ID is 0, refreshing cookie...")
                try:
                    refresh_cookie(driver)
                    pinned_ids = get_pinned_posts(driver)
                    post_id, ext = get_latest_post(driver, pinned_ids)
                    if post_id == 0:
                        print("Post ID is still 0, Chrome Cookie refresh:")
                        chrome_refresh_cookie()
                        pinned_ids = get_pinned_posts(driver)
                        post_id, ext = get_latest_post(driver, pinned_ids)
                        if post_id == 0:
                            print("Post ID is still 0, restarting the loop")
                            continue
                except Exception as e:
                    print(f"Error refreshing cookie: {e}")
                    continue

            try:
                with connection.cursor() as cursor:
                    sql = "SELECT * FROM instagram WHERE notpost_id = %s"
                    cursor.execute(sql, (post_id,))
                    foundInTable = cursor.fetchone()
                    if not foundInTable:
                        sql = "SELECT * FROM instagram WHERE post_id = %s"
                        cursor.execute(sql, (post_id,))
                        foundInTable = cursor.fetchone()
            except Exception as e:
                print(f"Database error: {e}")
                continue

            if not foundInTable:
                print(f"NEW POST FROM {USER} AT {datetime.now()}")
                try:
                    if ext == '.mp4':
                        gallery_url = f'https://www.instagram.com/reel/{post_id}/'
                        subprocess.run(["gallery-dl", gallery_url])
                        video_filename = next(
                            (f for f in os.listdir(f"./gallery-dl/instagram/{USER}/") if f.endswith((".mp4", ".mov", ".avi", ".mkv", ".webm", ".flv"))),
                            None
                        )
                        if not video_filename:
                            raise Exception("No video found.")
                        video_file_name = f'gallery-dl/instagram/{USER}/{video_filename}'
                        video_bytes = open(video_file_name, 'rb').read()
                        try:
                            response = client.models.generate_content(
                                model='models/gemini-2.0-flash',
                                contents=types.Content(parts=[
                                    types.Part(inline_data=types.Blob(data=video_bytes, mime_type='video/mp4')),
                                    types.Part(text=prompt)
                                ])
                            )
                        except Exception as e:
                            print(f"IA error: {e}")
                            shutil.rmtree('gallery-dl')
                            continue

                    elif ext == '.png':
                        gallery_url = f'https://www.instagram.com/p/{post_id}/'
                        subprocess.run(["gallery-dl", gallery_url])
                        image_filename = next(
                            (f for f in os.listdir(f"./gallery-dl/instagram/{USER}/") if f.lower().endswith((".jpg", ".jpeg", ".png", ".webp", ".bmp", ".gif"))),
                            None
                        )
                        if not image_filename:
                            raise Exception("No image found.")
                        image_path = f'gallery-dl/instagram/{USER}/{image_filename}'
                        image_bytes = open(image_path, 'rb').read()
                        image = types.Part.from_bytes(data=image_bytes, mime_type="image/jpeg")
                        try:
                            response = client.models.generate_content(
                                model="models/gemini-2.0-flash",
                                contents=[prompt, image],
                            )
                        except Exception as e:
                            print(f"IA error: {e}")
                            shutil.rmtree('gallery-dl')
                            continue

                    shutil.rmtree('gallery-dl')

                    try:
                        response_text = response.text.strip()
                        isMedia = True if response_text == "1" else False if response_text == "0" else None
                    except Exception as e:
                        print(f"Error with the response: {e}")
                        isMedia = None

                    print(f"https://www.instagram.com/p/{post_id}/ - {USER} - {isMedia}")

                    try:
                        with connection.cursor() as cursor:
                            sql = "INSERT INTO instagram (poster_name, post_id, notpost_id) VALUES (%s, %s, %s)"
                            if isMedia == True:
                                cursor.execute(sql, (USER, post_id, ''))
                            elif isMedia == False:
                                cursor.execute(sql, (USER, '', post_id))
                            connection.commit()
                    except Exception as e:
                        print(f"Error in the insert to the DB: {e}")

                except Exception as e:
                    print(f"Error from post from {USER}: {e}")
                    try:
                        shutil.rmtree('gallery-dl')
                    except:
                        pass
                    continue
        except Exception as e:
            print(f"Error with user {USER}: {e}")
            continue

    time.sleep(5 * 60)
