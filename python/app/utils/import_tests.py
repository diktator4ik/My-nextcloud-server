import json
import os
from sqlalchemy.orm import Session
from sqlalchemy.dialects.postgresql import insert  
from app import crud, schemas
from app.models import Topic  

def load_tests_from_json(db: Session, folder_path: str):
    for filename in os.listdir(folder_path):
        if filename.endswith(".json"):
            with open(os.path.join(folder_path, filename), "r") as f:
                data = json.load(f)
            
            topic_name = data.get("topic") or data.get("name")  # <- спробуємо кілька варіантів
            if not topic_name:
                print(f"❌ File {filename} не містить назви топіку")
                continue
            stmt = insert(Topic).values(name=topic_name).on_conflict_do_nothing(index_elements=['name'])
            db.execute(stmt)
            db.commit()
            topic = next((t for t in crud.get_topics(db) if t.name == topic_name), None)
            if not topic:
                topic = crud.create_topic(db, topic_name)
            
            question_data = schemas.QuestionCreate(
                topic_id=topic.id,
                question=data["question"],
                variants=data["variants"],
                answer=data["answer"],
                explanation=data["explanation"]
            )
            crud.add_question(db, question_data)
    print("✅ Tests imported successfully!")

