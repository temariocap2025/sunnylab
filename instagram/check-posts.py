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

USERS = ["colegiocapouilliez", "mineducgt"]
COOKIE = "75508383702:Mso88zNPDrxxZg:4:AYcj-w0JKKa9VgPRecZrWrBQkTESMxwnwt37L-LGIA" #rezar para que no se tenga que refrescar

connection = pymysql.connect(
    host="localhost", port=3306, user="temario", passwd="temario", database="db_solmaforo"
)

chrome_options = Options()
chrome_options.add_argument("--headless")
chrome_options.add_argument("--disable-blink-features=AutomationControlled")
service = Service("/usr/bin/chromedriver")
driver = webdriver.Chrome(service=service, options=chrome_options)

client = genai.Client(api_key='AIzaSyD4y3882Q0IZjitGCsxxVMafL4RtU8QNeM')

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

        pinned_icons = driver.find_elements(By.CSS_SELECTOR, 'svg[aria-label="Pinned post icon"]')
        pinned_post_ids = []
        for icon in pinned_icons:
            try:
                link = icon.find_element(By.XPATH, "./ancestor::a[1]")
                href = link.get_attribute("href")
                if "/p/" in href:
                    shortcode = href.split("/p/")[1].split("/")[0]
                    pinned_post_ids.append(shortcode)
                if "/reel/" in href:
                    shortcode = href.split("/reel/")[1].split("/")[0]
                    pinned_post_ids.append(shortcode)
            except Exception as e:
                print("Error getting pinned post ID:", e)

        posts = driver.find_elements(By.CLASS_NAME, '_aagu')
        post_id = 0
        ext = '.png'
        for post in posts:
            try:
                link = post.find_element(By.XPATH, "./ancestor::a[1]")
                href = link.get_attribute("href")
                if "/p/" in href:
                    shortcode = href.split("/p/")[1].split("/")[0]
                    if shortcode not in pinned_post_ids:
                        post_id = shortcode
                        ext = '.png'
                        break
                if "/reel/" in href:
                    shortcode = href.split("/reel/")[1].split("/")[0]
                    if shortcode not in pinned_post_ids:
                        post_id = shortcode
                        ext = '.mp4'
                        break
            except Exception as e:
                print("Error getting post ID:", e)

        with connection.cursor() as cursor:
            sql = "SELECT * FROM instagram WHERE notpost_id = %s"
            cursor.execute(sql, (post_id,))
            foundInTable = cursor.fetchone()

            if not foundInTable:
                sql = "SELECT * FROM instagram WHERE post_id = %s"
                cursor.execute(sql, (post_id,))
                result = cursor.fetchone()

        if not foundInTable:
            print(f"NEW POST FROM {USER}")
            if ext == '.mp4':
                gallery_url = f'https://www.instagram.com/reel/{post_id}/'
                try:
                    subprocess.run(["gallery-dl", gallery_url])
                    video_filename = None
                    for file in os.listdir(f"./gallery-dl/instagram/{USER}/"):
                        if file.endswith(".mp4"):
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
                            types.Part(text='Respondeme unicamente un 0 o un 1, este comunicado habla sobre algun motivo ambiental (suspension de clases o recomendaciones a un evento)?')
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
                        if file.endswith(".jpg"):
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
                        'Respondeme unicamente un 0 o un 1, este comunicado habla sobre algun motivo ambiental (suspension de clases o recomendaciones a un evento)?',
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
                    sql = "INSERT INTO instagram (post_id, notpost_id) VALUES (%s, %s)"
                    cursor.execute(sql, (post_id, ''))
                    connection.commit()
            elif isMedia == False:
                with connection.cursor() as cursor:
                    sql = "INSERT INTO instagram (post_id, notpost_id) VALUES (%s, %s)"
                    cursor.execute(sql, ('', post_id))
                    connection.commit()

            isMedia = None

    time.sleep(5*60)
