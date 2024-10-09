import React, { useState } from 'react';
import { Button, Modal } from 'react-bootstrap';
import { useTranslation } from 'react-i18next';

function CommentModal({
  comment,
  isComModal,
  setIsComModal,
  handleComment,
  handleDeleteComment,
  taskComments,
  handleEditComment,
  taskName,
  setComments,
  isEditable,
  setIsEditable,
  userType
}) {
  const { t } = useTranslation();
  const [editingCommentId, setEditingCommentId] = useState(null); // Comment being edited
  const [editedComment, setEditedComment] = useState(''); // Track edited comment's value

  const handleCommentChange = (e) => {
    setEditedComment(e.target.value); // Track changes to the comment being edited
  };

  const handleUpdateComment = async (cid) => {
    await handleEditComment(cid, editedComment); // Save the updated comment
    setEditingCommentId(null); // Reset edit mode after updating
    setIsEditable(false); // Disable edit mode
  };

  return (
    <div>
      <Modal
        size="lg"
        className="modal-container"
        show={isComModal}
        onHide={() => {
          setIsComModal(false);
        }}
      >
        <Modal.Header closeButton>
          <Modal.Title>{taskName}</Modal.Title>
        </Modal.Header>

        <Modal.Body>
          <div className="d-flex flex-column mb-3">
            <div className="d-flex flex-column">
              <p className="navblueColor mb-2 font-18" style={{ fontWeight: '500' }}>Add Comments</p>
              <textarea
                value={comment}
                onChange={(e) => setComments(e.target.value)} // Simple textarea input for comments
                className="form-control"
                rows="3"
                placeholder="Write your comment..."
              />
            </div>

            <div className="comments-section mt-4">
              {taskComments.length > 0 ? (
                taskComments.map((com) => (
                  <div key={com.id} className="comment-item d-flex align-items-start mb-3">
                    <div className="comment-author-icon mr-3">
                      <div
                        className="circle-icon d-flex align-items-center ml-1 justify-content-center"
                        style={{
                          width: '40px',
                          height: '40px',
                          borderRadius: '50%',
                          backgroundColor: '#007bff',
                          color: '#fff',
                          fontSize: '18px',
                        }}
                      >
                        {com.commentable_type === 'App\\Models\\Admin' ? 'A' : 'U'}
                      </div>
                    </div>

                    <div className="comment-content w-100">
                      <div className="mb-1" style={{ border: '1px solid #DDDDDD', padding: '10px' }}>
                        <div className="d-flex justify-content-between align-items-center">
                          <span className="comment-author-name font-weight-bold">
                            {com.commentable_type === 'App\\Models\\Admin' ? 'Admin' : 'User'}
                          </span>
                          <span className="comment-date text-muted">
                            {new Date(com.created_at).toLocaleString()}
                          </span>
                        </div>

                        {editingCommentId === com.id && isEditable ? (
                          <input
                            type="text"
                            className="form-control pt-0 px-2 py-1 comment-text border-0 mt-2"
                            value={editedComment}
                            onChange={handleCommentChange}
                          />
                        ) : (
                          <div className="comment-text pt-0 px-2 py-1 mt-2">{com.comment}</div>
                        )}
                      </div>

                      {/* Show Edit/Delete buttons based on userType and commentable_type */}
                      {userType === 'admin' || (userType === 'worker' && com.commentable_type === 'App\\Models\\User') ? (
                        <div className="comment-actions d-flex justify-content-end">
                          {editingCommentId === com.id ? (
                            <button
                              className="btn btn-link p-0 mr-3"
                              onClick={() => handleUpdateComment(com.id)}
                            >
                              Update
                            </button>
                          ) : (
                            <button
                              className="btn btn-link p-0 mr-3"
                              onClick={() => {
                                setEditingCommentId(com.id);
                                setEditedComment(com.comment);
                                setIsEditable(true);
                              }}
                            >
                              Edit
                            </button>
                          )}
                          <button
                            className="btn btn-link p-0 text-danger"
                            onClick={() => handleDeleteComment(com.id)}
                          >
                            Delete
                          </button>
                        </div>
                      ) : null} {/* Only show buttons if admin or if user is the comment owner */}
                    </div>
                  </div>
                ))
              ) : (
                <p>No comments yet.</p>
              )}
            </div>
          </div>
        </Modal.Body>

        <Modal.Footer>
          <Button
            type="button"
            className="btn btn-secondary"
            onClick={() => setIsComModal(false)}
          >
            {t('modal.close')}
          </Button>
          <Button type="button" className="btn btn-primary" onClick={handleComment}>
            {t('modal.add')}
          </Button>
        </Modal.Footer>
      </Modal>
    </div>
  );
}

export default CommentModal;
