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
import shutil
import tempfile
from datetime import datetime

USERS = ["colegiocapouilliez", "mineducgt", "tn23noticias", "conredgt"]
prompt = 'Este video o imagen habla específicamente sobre un tema ambiental relacionado con Guatemala? o suspención de clases? (Desastres naturales, radiación UV, calidad del aire, temperatura, humedad, o recomendaciones sobre estos temas). Responde con un 1 o un 0 únicamente (temas prohibidos: Drogas, casos de otros países que no involucran Guatemala)'

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

config_path = "/home/temario/.config/gallery-dl/config.json" # gallery-dl
client = genai.Client(api_key='AIzaSyD4y3882Q0IZjitGCsxxVMafL4RtU8QNeM')

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

def manual_refresh_cookie():
    manual_options = Options()
    manual_options.add_experimental_option("detach", True)
    manual_driver = webdriver.Chrome(service=service, options=manual_options)
    manual_driver.get("https://www.instagram.com/")
    print("Inicia sesión, presiona ENTER cuando ya estes loggeado")
    input()
    cookies = manual_driver.get_cookies()
    sessionid_cookie = None
    for cookie in cookies:
        if cookie['name'] == 'sessionid':
            sessionid_cookie = cookie['value']
            break
    manual_driver.quit()
    if not sessionid_cookie:
        return
    print(f"SessionID: {sessionid_cookie}")

    update_gallerydl(sessionid_cookie)
    global COOKIE
    COOKIE = sessionid_cookie

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
        driver.get(f"https://www.instagram.com/{USER}/")

        driver.add_cookie({
            "name": "sessionid",
            "value": COOKIE,
            "domain": ".instagram.com"
        })
        driver.get(f"https://www.instagram.com/{USER}/")
        time.sleep(5)

        pinned_ids = get_pinned_posts(driver)
        post_id, ext = get_latest_post(driver, pinned_ids)

        if post_id == 0:
            print("Post ID is 0, refreshing cookie...")
            refresh_cookie(driver)

            pinned_ids = get_pinned_posts(driver)
            post_id, ext = get_latest_post(driver, pinned_ids)

            if post_id == 0:
                print("Post ID is still 0, Chrome Cookie refresh:")
                chrome_refresh_cookie()

                pinned_ids = get_pinned_posts(driver)
                post_id, ext = get_latest_post(driver, pinned_ids)
                
                if post_id == 0:
                    print("Post ID is still 0, Waiting 30 minutes...")
                    time.sleep(30 * 60)
                    continue

        with connection.cursor() as cursor:
            sql = "SELECT * FROM instagram WHERE notpost_id = %s"
            cursor.execute(sql, (post_id,))
            foundInTable = cursor.fetchone()

            if not foundInTable:
                sql = "SELECT * FROM instagram WHERE post_id = %s"
                cursor.execute(sql, (post_id,))
                foundInTable = cursor.fetchone()

        if not foundInTable:
            print(f"NEW POST FROM {USER} AT {datetime.now()}")
            if ext == '.mp4':
                gallery_url = f'https://www.instagram.com/reel/{post_id}/'
                try:
                    subprocess.run(["gallery-dl", gallery_url])
                    video_filename = None
                    for file in os.listdir(f"./gallery-dl/instagram/{USER}/"):
                        if file.endswith((".mp4", ".mov", ".avi", ".mkv", ".webm", ".flv")):
                            video_filename = file
                            break
                except subprocess.CalledProcessError as e:
                    print("Error running gallery-dl:")
                    print(e.stderr)

                video_file_name = f'gallery-dl/instagram/{USER}/{video_filename}'
                video_bytes = open(video_file_name, 'rb').read()

                response = client.models.generate_content(
                    model='models/gemini-2.0-flash',
                    contents=types.Content(
                        parts=[
                            types.Part(
                                inline_data=types.Blob(data=video_bytes, mime_type='video/mp4')
                            ),
                            types.Part(text=prompt)
                        ]
                    )
                )
                shutil.rmtree('gallery-dl')
            elif ext == '.png':
                gallery_url = f'https://www.instagram.com/p/{post_id}/'
                try:
                    subprocess.run(["gallery-dl", gallery_url])
                    image_filename = None
                    for file in os.listdir(f"./gallery-dl/instagram/{USER}/"):
                        if file.lower().endswith((".jpg", ".jpeg", ".png", ".webp", ".bmp", ".gif")):
                            image_filename = file
                            break
                except subprocess.CalledProcessError as e:
                    print("Error running gallery-dl:")
                    print(e.stderr)

                image_path = f'gallery-dl/instagram/{USER}/{image_filename}'
                image_bytes = open(image_path, 'rb').read()
                image = types.Part.from_bytes(
                    data=image_bytes, mime_type="image/jpeg"
                )

                response = client.models.generate_content(
                    model="models/gemini-2.0-flash",
                    contents=[
                        prompt,
                        image
                    ],
                )
                shutil.rmtree('gallery-dl')

            response_text = response.text.strip()
            if response_text == "1":
                isMedia = True
            elif response_text == "0":
                isMedia = False
            else:
                print(f"AI: {response_text}")
                isMedia = None

            print(f"https://www.instagram.com/p/{post_id}/ - {USER} - {isMedia}")

            if isMedia == True:
                with connection.cursor() as cursor:
                    sql = "INSERT INTO instagram (poster_name, post_id, notpost_id) VALUES (%s, %s, %s)"
                    cursor.execute(sql, (USER, post_id, ''))
                    connection.commit()
            elif isMedia == False:
                with connection.cursor() as cursor:
                    sql = "INSERT INTO instagram (poster_name, post_id, notpost_id) VALUES (%s, %s, %s)"
                    cursor.execute(sql, (USER, '', post_id))
                    connection.commit()

            isMedia = None

    time.sleep(5 * 60)
