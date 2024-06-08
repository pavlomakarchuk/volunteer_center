import pandas as pd
import sqlite3
from datetime import datetime, timedelta

# Load the spreadsheet
file_path = 'center_database.xlsx'
spreadsheet = pd.ExcelFile(file_path)

# Load the 'Список' sheet
df_splist = spreadsheet.parse('Список')

# Selecting the relevant columns for the VPO table
df_vpo = df_splist[['vpo_name', 'replacement_date', 'dovidka', 'region', 'city', 'mobile', 'adress']]
df_vpo.columns = ['vpo_name', 'replacement_date', 'dovidka', 'region', 'city', 'mobile', 'address']

# Remove duplicates based on 'dovidka'
df_vpo = df_vpo.drop_duplicates(subset='dovidka')

# Connect to the SQLite database
conn = sqlite3.connect('center_database.db')
cursor = conn.cursor()

# Create the VPO table
create_vpo_table_query = """
CREATE TABLE IF NOT EXISTS VPO (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    vpo_name TEXT NOT NULL,
    replacement_date TEXT NOT NULL,
    dovidka TEXT NOT NULL UNIQUE,
    region TEXT NOT NULL,
    city TEXT NOT NULL,
    mobile TEXT NOT NULL,
    address TEXT NOT NULL
);
"""
cursor.execute(create_vpo_table_query)

# Insert data into the VPO table
insert_vpo_query = """
INSERT OR IGNORE INTO VPO (vpo_name, replacement_date, dovidka, region, city, mobile, address)
VALUES (?, ?, ?, ?, ?, ?, ?)
"""

# Convert DataFrame to list of tuples
vpo_data = df_vpo.dropna().to_records(index=False).tolist()

# Insert the data into the table
cursor.executemany(insert_vpo_query, vpo_data)
conn.commit()

# Prepare data for the arrivals table
df_arrivals = df_splist[['dovidka', 'arrival_dates']].dropna()

# Create the arrivals table
create_arrivals_table_query = """
CREATE TABLE IF NOT EXISTS arrivals (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    dovidka TEXT NOT NULL,
    arrival_date TEXT NOT NULL,
    next_date TEXT NOT NULL,
    FOREIGN KEY (dovidka) REFERENCES VPO(dovidka)
);
"""
cursor.execute(create_arrivals_table_query)

# Insert data into the arrivals table

insert_arrivals_query = """
INSERT INTO arrivals (dovidka, arrival_date, next_date)
VALUES (?, ?, ?)
"""

def parse_date(date_str):
    for fmt in ('%d.%m.%Y', '%d.%m.%y'):
        try:
            return datetime.strptime(date_str, fmt)
        except ValueError:
            pass
    raise ValueError(f"Date format not recognized: {date_str}")

arrivals_data = []
for _, row in df_arrivals.iterrows():
    dovidka = row['dovidka']
    arrival_dates = row['arrival_dates'].split()
    for arrival_date in arrival_dates:
        try:
            arrival_date_dt = parse_date(arrival_date)
        except ValueError:
            continue
        next_date_dt = arrival_date_dt + timedelta(weeks=5)
        arrivals_data.append((dovidka, arrival_date_dt.strftime('%Y-%m-%d'), next_date_dt.strftime('%Y-%m-%d')))

cursor.executemany(insert_arrivals_query, arrivals_data)
conn.commit()

# Verify insertion
cursor.execute("SELECT * FROM VPO LIMIT 5")
vpo_entries = cursor.fetchall()
cursor.execute("SELECT * FROM arrivals LIMIT 5")
arrivals_entries = cursor.fetchall()

# Close the connection
conn.close()

print("VPO Entries:", vpo_entries)
print("Arrivals Entries:", arrivals_entries)
