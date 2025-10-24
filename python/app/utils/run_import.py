from app.utils.import_tests import load_tests_from_json
from app.database import SessionLocal
from app import crud, schemas

if __name__ == "__main__":
    db = SessionLocal()
    folder_path = "/home/diktator4ik/python/output"  # шлях до JSON-файлів
    load_tests_from_json(db, folder_path)

