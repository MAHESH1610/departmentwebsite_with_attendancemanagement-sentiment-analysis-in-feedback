from pydantic import BaseModel

class Scholarship(BaseModel):
    mark: int
    attendance: int
    backlogs: int
    family_income: int
    sports_or_not: int