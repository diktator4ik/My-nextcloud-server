from fastapi import APIRouter, Depends
from sqlalchemy.orm import Session
from app.database import get_db
from app import crud, schemas

router = APIRouter()

@router.get("/topics", response_model=list[schemas.TopicOut])
def list_topics(db: Session = Depends(get_db)):
    return crud.get_topics(db)

@router.post("/topics", response_model=schemas.TopicOut)
def create_topic(name: str, db: Session = Depends(get_db)):
    return crud.create_topic(db, name)

