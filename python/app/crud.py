from sqlalchemy.orm import Session
from app import models, schemas

def get_topics(db: Session):
    print("4len 4len 4len")
    return db.query(models.Topic).all()

def create_topic(db: Session, name: str):
    topic = models.Topic(name=name)
    db.add(topic)
    db.commit()
    db.refresh(topic)
    return topic

def get_questions_by_topic(db: Session, topic_id: int):
    return db.query(models.Question).filter(models.Question.topic_id == topic_id).all()

def add_question(db: Session, data: schemas.QuestionCreate):
    q = models.Question(**data.dict())
    db.add(q)
    db.commit()
    db.refresh(q)
    return q

