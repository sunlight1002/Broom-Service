import { Base64 } from "js-base64";
import React, { useState, useEffect } from "react";
import { Link, useParams } from "react-router-dom";

export default function WorkerContract() {
    const params = useParams();
    const id = params.id;
    const [form, setForm] = useState(false);
    const [workerId, setWorkerId] = useState("");
    const getForm = () => {
        axios.get(`/api/work-contract/${id}`).then((res) => {
            setForm(res.data.form ? true : false);
            setWorkerId(res.data.worker.worker_id);
        });
    };
    useEffect(() => {
        getForm();
    }, []);

    return (
        <div className="container">
            {form && workerId ? (
                <div>
                    <button type="button" className="btn btn-success m-3">
                        Signed
                    </button>
                    <Link
                        target="_blank"
                        to={`/worker-contract/` + Base64.encode(workerId)}
                        className="m-2 btn btn-pink"
                    >
                        View Contract
                    </Link>
                </div>
            ) : (
                <button type="button" className="btn btn-danger">
                    Not Signed{" "}
                </button>
            )}
        </div>
    );
}
