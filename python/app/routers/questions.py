from fastapi import APIRouter, Depends
from sqlalchemy.orm import Session
from app.database import get_db
from app import crud, schemas

router = APIRouter()

@router.get("/questions/by_topic/{topic_id}", response_model=list[schemas.QuestionOut])
def get_by_topic(topic_id: int, db: Session = Depends(get_db)):
    return crud.get_questions_by_topic(db, topic_id)

@router.post("/", response_model=schemas.QuestionOut)
def add_question(data: schemas.QuestionCreate, db: Session = Depends(get_db)):
    return crud.add_question(db, data)

