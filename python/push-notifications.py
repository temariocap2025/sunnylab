import onesignal
from onesignal.api import default_api
from onesignal.model.notification import Notification
from onesignal.model.string_map import StringMap
from pprint import pprint
import time
import pymysql

configuration = onesignal.Configuration(
    app_key="os_v2_app_j6hohn6l6rdvliljrgkv6abn6ybnebu6ij4ek6nwh2bwxz5e3kzlhggi7sfkfvfzodmfkvfpcb5k5wsbzdhjfgwo33ohyf4zcwxkjuq"
)
connection = pymysql.connect(
    host="localhost", port=3306, user="temario", passwd="temario", database="db_solmaforo", autocommit=True
)

state = {
    "uv_index": False,
    "ica_index": False
}

high_values = {
    "uv_index": 8,
    "ica_index": 150
}
name = {
    "uv_index": "Radiación Ultravioleta",
    "ica_index": "Contaminación de Aire"
}
channel_id = {
    "uv_index": "a9ef2772-565b-469e-b171-4501108c562a",
    "ica_index": "a9ef2772-565b-469e-b171-4501108c562a"
}

while True:
    with connection.cursor() as cursor:
        sql = "SELECT * FROM mediciones ORDER BY id_medicion DESC LIMIT 1"
        cursor.execute(sql)
        row = cursor.fetchone()
        if row:
            columns = [desc[0] for desc in cursor.description]
            data = dict(zip(columns, row))

            for value in high_values:
                if value in data and data[value] >= high_values[value] and not state[value]:
                    with onesignal.ApiClient(configuration) as api_client:
                        api_instance = default_api.DefaultApi(api_client)
                        notification = Notification(
                            app_id="4f8ee3b7-cbf4-4755-a169-89955f002df6",
                            included_segments=["All"],
                            headings=StringMap(en="SunnyLab"),
                            contents=StringMap(en=f"{name[value]} muy alto: {data[value]}"),
                            android_channel_id=channel_id[value]
                        )
                        try:
                            api_response = api_instance.create_notification(notification)
                            pprint(api_response)
                            state[value] = True
                        except onesignal.ApiException as e:
                            print("No se pudo enviar la notificación: %s\n" % e)
    time.sleep(10)
