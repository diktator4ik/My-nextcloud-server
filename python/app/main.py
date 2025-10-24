from fastapi import FastAPI
from app.routers import topics, questions
from app.database import Base, engine

Base.metadata.create_all(bind=engine)

app = FastAPI(title="USMLE Test API")

app.include_router(topics.router)
app.include_router(questions.router)

@app.get("/")
def root():
    return {"message": "USMLE API is running!"}

