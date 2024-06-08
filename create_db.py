import sqlite3
import os

# Повертає абсолютний шлях до бази даних
db_path = os.path.join(os.path.dirname(__file__), '../backend/volunteers.db')

conn = sqlite3.connect(db_path)
c = conn.cursor()

# Створення таблиці переселенців (VPO)
c.execute('''
    CREATE TABLE IF NOT EXISTS VPO (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        vpo_name TEXT NOT NULL,
        replacement_date TEXT NOT NULL,
        dovidka TEXT NOT NULL UNIQUE,
        region TEXT NOT NULL,
        city TEXT NOT NULL,
        mobile TEXT NOT NULL,
        address TEXT NOT NULL
    )
''')

# Створення таблиці візитів (arrivals)
c.execute('''
    CREATE TABLE IF NOT EXISTS arrivals (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        dovidka TEXT NOT NULL,
        arrival_date TEXT NOT NULL,
        next_date TEXT NOT NULL,
        FOREIGN KEY (dovidka) REFERENCES VPO(dovidka)
    )
''')

# Створення таблиці новин (news)
c.execute('''
    CREATE TABLE IF NOT EXISTS news (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title TEXT NOT NULL,
        date TEXT NOT NULL,
        content TEXT NOT NULL
    )
''')

conn.commit()
conn.close()

print("База даних та таблиці успішно створені.")