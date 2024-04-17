import React,{ useState,useEffect } from 'react'
import { useParams, useNavigate, Link } from "react-router-dom";
import { Base64 } from "js-base64";

export default function WorkerSafeandGear() {

    const params = useParams();
    const id = params.id;
    const [form, setForm] = useState(false);
    const getForm = () => {
        axios.get(`/api/getSafegear/${id}`).then((res) => {
            if (res.data.form) {
                setForm(true);
            } else {
                setForm(false);
            }
        });
    };
    useEffect(() => {
        getForm();
    }, []);
  return (
    <div
        className="tab-pane fade active show"
        id="customer-notes"
        role="tabpanel"
        aria-labelledby="customer-notes-tab"
    >
        <div className="container1">
            {form == true ? (
                <>
                    <span className="btn btn-success">Signed</span>
                    <p>
                        <Link
                            target="_blank"
                            to={`/worker-safe-gear/` + Base64.encode(id.toString())}
                            className="m-2"
                        >
                            View Safe and Gear
                        </Link>
                    </p>
                </>
            ) : (
                <>
                    <span className="btn btn-warning">Not Signed</span>
                </>
            )}
        </div>
    </div>
  )
}
