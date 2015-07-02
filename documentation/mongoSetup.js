db.elevator.ensureIndex({"readyForDisplay": 1});
db.elevator.ensureIndex({"modifiedDate": 1});
db.elevator.ensureIndex({"modifiedBy": 1});
db.elevator.ensureIndex({"agent_id.fieldContents": 1});
db.elevator.ensureIndex({"order_id.fieldContents": 1});
db.elevator.ensureIndex({"digital_id.fieldContents": 1});
db.elevator.ensureIndex({"work_id.fieldContents": 1});
db.elevator.ensureIndex({"source_id.fieldContents": 1});
db.elevator.ensureIndex({"view_id.fieldContents": 1});
db.elevator.ensureIndex({"templateId": 1});
db.elevator.ensureIndex({"collectionId": 1});
db.elevator.ensureIndex({"modified": 1});

db.fileRepository.ensureIndex({"type": 1});

db.history.ensureIndex({"deleted": 1});
db.history.ensureIndex({"sourceId": 1});
