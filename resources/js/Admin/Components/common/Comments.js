import React, { useState, useEffect, useRef } from "react";
import axios from "axios";
import Swal from "sweetalert2";
import moment from "moment";

import AddCommentModal from "../Modals/AddCommentModal";

export default function Comments({
    relationID,
    routeType,
    canAddComment = true,
}) {
    const [comments, setComments] = useState([]);
    const [loading, setLoading] = useState("Loading..");
    const [isOpenAddComment, setIsOpenAddComment] = useState(false);

    const cmtFileRef = useRef(null);

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };

    const getComments = () => {
        axios
            .get(`/api/admin/${routeType}/${relationID}/comments`, { headers })
            .then((res) => {
                if (res.data.comments.length > 0) {
                    setComments(res.data.comments);
                } else {
                    setComments([]);
                    setLoading("No comment found");
                }
            });
    };

    const handleDelete = (id) => {
        Swal.fire({
            title: "Are you sure?",
            text: "You won't be able to revert this!",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Yes, Delete Comment!",
        }).then((result) => {
            if (result.isConfirmed) {
                axios
                    .delete(
                        `/api/admin/${routeType}/${relationID}/comments/${id}`,
                        { headers }
                    )
                    .then((response) => {
                        Swal.fire(
                            "Deleted!",
                            "Comment has been deleted.",
                            "success"
                        );
                        setTimeout(() => {
                            getComments();
                        }, 1000);
                    });
            }
        });
    };

    useEffect(() => {
        getComments();
    }, []);

    const handleAddComment = () => {
        setIsOpenAddComment(true);
    };

    return (
        <div className="boxPanel">
            {canAddComment && (
                <>
                    <div className="action-dropdown dropdown text-right mb-3">
                        <button
                            className="btn btn-primary mr-3"
                            onClick={(e) => handleAddComment(e)}
                        >
                            Add Comment
                        </button>
                    </div>

                    {isOpenAddComment && (
                        <AddCommentModal
                            relationID={relationID}
                            routeType={routeType}
                            isOpen={isOpenAddComment}
                            setIsOpen={setIsOpenAddComment}
                            onSuccess={() => getComments()}
                        />
                    )}
                </>
            )}

            <div className="table-responsive">
                {comments.length > 0 ? (
                    <table className="table table-bordered">
                        <thead>
                            <tr>
                                <th>Comment</th>
                                <th>Commented At</th>
                                <th>Commented By</th>
                                <th>Valid Till</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            {comments.map((comment, i) => {
                                return (
                                    <tr key={i}>
                                        <td>
                                            {comment.comment}
                                            <br />
                                            {comment.attachments.map(
                                                (attachment, i) => {
                                                    return (
                                                        <span
                                                            className="badge badge-warning text-dark"
                                                            key={i}
                                                        >
                                                            <a
                                                                onClick={(
                                                                    e
                                                                ) => {
                                                                    cmtFileRef.current.setAttribute(
                                                                        "src",
                                                                        `/storage/uploads/attachments/${attachment.file_name}`
                                                                    );
                                                                    cmtFileRef.current.style.display =
                                                                        "block";
                                                                }}
                                                                data-toggle="modal"
                                                                data-target="#commentAttachment"
                                                                style={{
                                                                    cursor: "pointer",
                                                                }}
                                                            >
                                                                {
                                                                    attachment.original_name
                                                                }
                                                            </a>
                                                        </span>
                                                    );
                                                }
                                            )}
                                        </td>
                                        <td>
                                            {moment(comment.created_at).format(
                                                "D, MMM YYYY HH:mm"
                                            )}
                                        </td>
                                        <td>{comment.commenter_name}</td>
                                        <td>
                                            {comment.valid_till
                                                ? moment(
                                                      comment.valid_till
                                                  ).format("D, MMM YYYY")
                                                : "NA"}
                                        </td>
                                        <td>
                                            <div className="d-flex">
                                                <button
                                                    className="ml-2 btn bg-red"
                                                    onClick={() =>
                                                        handleDelete(comment.id)
                                                    }
                                                >
                                                    <i className="fa fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                );
                            })}
                        </tbody>
                    </table>
                ) : (
                    <div className="form-control text-center">{loading}</div>
                )}
            </div>

            <div
                className="modal fade"
                id="commentAttachment"
                tabIndex="-1"
                role="dialog"
                aria-labelledby="commentAttachmentLabel"
                aria-hidden="true"
            >
                <div className="modal-dialog" role="document">
                    <div className="modal-content" style={{ width: "130%" }}>
                        <div className="modal-header">
                            <button
                                type="button"
                                className="close"
                                data-dismiss="modal"
                                aria-label="Close"
                            >
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div className="modal-body">
                            <div className="row">
                                <div className="col-sm-12">
                                    <div className="form-group">
                                        <img
                                            src=""
                                            className="form-control"
                                            ref={cmtFileRef}
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}
