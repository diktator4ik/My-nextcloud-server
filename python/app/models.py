from sqlalchemy import Column, Integer, String, ForeignKey, JSON
from sqlalchemy.orm import relationship
from app.database import Base

class Topic(Base):
    __tablename__ = "topics"
    id = Column(Integer, primary_key=True, index=True)
    name = Column(String, unique=True, nullable=False)

    questions = relationship("Question", back_populates="topic")

class Question(Base):
    __tablename__ = "questions"
    id = Column(Integer, primary_key=True, index=True)
    topic_id = Column(Integer, ForeignKey("topics.id"))
    question = Column(String, nullable=False)
    variants = Column(JSON, nullable=False)
    answer = Column(String, nullable=False)
    explanation = Column(String, nullable=False)
    image_url = Column(String, nullable=True)

    topic = relationship("Topic", back_populates="questions")

