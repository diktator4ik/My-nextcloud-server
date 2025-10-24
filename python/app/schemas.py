from pydantic import BaseModel
from typing import List, Optional

class QuestionBase(BaseModel):
    question: str
    variants: List[str]
    answer: str
    explanation: str
    image_url: Optional[str] = None

class QuestionCreate(QuestionBase):
    topic_id: int

class QuestionOut(QuestionBase):
    id: int
    topic_id: int
    class Config:
        orm_mode = True

class TopicBase(BaseModel):
    name: str

class TopicOut(TopicBase):
    id: int
    questions: Optional[List[QuestionOut]] = []
    class Config:
        orm_mode = True

