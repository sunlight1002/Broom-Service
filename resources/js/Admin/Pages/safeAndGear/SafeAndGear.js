import React, { useRef } from "react";
import Sidebar from "../../Layouts/Sidebar";
import * as yup from "yup";
import { useFormik } from "formik";
import TextField from "../../../Pages/Form101/inputElements/TextField";
import ReactSignatureCanvas from "react-signature-canvas";

const formSchema = yup.object({
    workerName: yup.string().trim().required("worker name is required"),
    workerName2: yup.string().trim().required("worker name is required"),
    signature: yup.mixed().required("Signature is required"),
});
const SafeAndGear = () => {
    const sigRef = useRef();
    const initialValues = {
        workerName: "",
        workerName2: "",
        signature: "",
    };
    const {
        errors,
        touched,
        handleBlur,
        handleChange,
        handleSubmit,
        values,
        setFieldValue,
    } = useFormik({
        initialValues,
        validationSchema: formSchema,
        onSubmit: (values) => {
            console.log("values", values);
        },
    });
    const handleSignatureEnd = () => {
        setFieldValue("signature", sigRef.current.toDataURL());
    };
    const clearSignature = () => {
        sigRef.current.clear();
        setFieldValue("signature", "");
    };
    return (
        <div id="container">
            <Sidebar />
            <div id="content">
                <div className="w-75 mx-auto mt-5 px-5">
                    <div className="text-center">
                        <h5>
                            <strong>Welcome to Broom Service:</strong>
                        </h5>
                    </div>
                    <p className="mt-4" style={{ fontSize: "16px" }}>
                        We are glad that you chose to work in our company, we
                        will do everything to make you happy and happy with your
                        work and of course take care of everything you need just
                        like a family. The job is a full-time job about 8 hours
                        a day and Fridays are optional.
                    </p>
                    <ol className="mt-3 lh-lg " style={{ fontSize: "16px" }}>
                        <li>
                            . Please make sure that all of the required forms
                            are signed and completed - signed contract, form
                            101, ID card or visa, and if necessary a health
                            form. In order not to delay the payment of wages at
                            the time.
                        </li>
                        <li>
                            . You will get adress for your work on whatsapp or
                            on your broom service application and how much time
                            will be required to work on each house.
                        </li>
                        <li>
                            . Please make sure to get to the client in time.
                            Please make sure to let us know if you are late or
                            if any problem.
                        </li>
                        <li>
                            Do not talk at all with the customers or next to
                            them about the number of hours worked or the salary
                            we receive, etc. Do not talk directly to the client
                            at all - only through your manager
                        </li>
                        <li>
                            clients may have cameras in their houses, please be
                            aware and as you work for us you agree to it and
                            aware to the situation as its mostly peoples private
                            houses and they might be watching us.
                        </li>
                        <li>
                            If any damage is done by mistake and while you work
                            please make sure to let us know about immediately so
                            we can inform the client.
                        </li>
                        <li>
                            . If u have any change on your schedule for next
                            week you need to let us know about your changes
                            until Monday on the week before.
                        </li>
                        <li>
                            At the end of every day, make sure all the equipment
                            is returned to the bag and there is no equipment
                            missing.
                        </li>
                    </ol>

                    <div className="mt-5">
                        <div className="text-center">
                            <h5>
                                <strong>Safety Procedure:</strong>
                            </h5>
                        </div>
                        <ol
                            className="mt-4 lh-lg "
                            style={{ fontSize: "16px" }}
                        >
                            <li>
                                Worker will only work with closed working shoes.
                            </li>
                            <li>
                                Working with materials like Bleach or acid or
                                Oils remover then you will need to use a mask
                                and gloves. If your missing it you need to let
                                us know to be provided to you before you use
                                those detergents or don’t use the detergents if
                                you don’t have the right protection and
                                equipment
                            </li>
                            <li>
                                No worker will work with cleaning materials
                                without protective gloves.
                            </li>
                            <li>
                                The worker cant touch any electricity, such as
                                an electrical outlet or a power board, without
                                any closed shoes. And only by a person who is
                                authorized to do so.
                            </li>
                            <li>
                                Do not clean balcony windows or other window
                                without instructions and as needed a proper
                                safety harness.
                            </li>
                            <li>
                                Do not squirt water in any form whatsoever on
                                the walls of the house or balcony that there is
                                a danger of electricity.
                            </li>
                            <li>
                                Do not climb a chair or anything else that
                                dangers you only on a standard ladderup to 2
                                meters.
                            </li>
                            <li>Do not mix any materials with each other.</li>
                            <li>Do not bend outside windows or balconies.</li>
                        </ol>
                    </div>
                    <div className="mt-5">
                        <div className="text-center">
                            <h5>
                                <strong>equipment list:</strong>
                            </h5>
                        </div>
                        <div></div>
                        <form onSubmit={handleSubmit}>
                            <div className="mt-4" style={{ fontSize: "16px" }}>
                                <TextField
                                    name={"workerName"}
                                    onBlur={handleBlur}
                                    onChange={handleChange}
                                    label={"Worker Name"}
                                    value={values.workerName}
                                    required={true}
                                    error={
                                        touched.workerName && errors.workerName
                                    }
                                />
                                <p>
                                    I __________________ declare that I have
                                    received the file with the attached
                                    equipment list and undertake to keep it if
                                    it is in my possession and return it intact
                                    at the end of my employment period at the
                                    company.
                                </p>
                                <p>
                                    I also took Broom Service work shirts and a
                                    back pack.
                                </p>
                                <p>
                                    I know that equipment that was under my hand
                                    and was damaged or not returned in its
                                    entirety, the cost of the product will be
                                    deducted from rent on the paycheck.
                                </p>
                                <p>
                                    I might also get some detergents as needed
                                    from the company I will use and collect back
                                    after the job is done.
                                </p>
                                <p>
                                    *Bleach and acid can be used only after
                                    approval of the manager and the right
                                    instructions and protection
                                </p>
                                <p>
                                    * At the end of each working day, clean out
                                    filters and empty vacuum cleaner if you use
                                    it
                                </p>
                                <p>*. Wet rags and scabs to hang for drying</p>
                                <div className="row gap-5">
                                    <div className="col-6">
                                        <TextField
                                            name={"workerName2"}
                                            onBlur={handleBlur}
                                            onChange={handleChange}
                                            label={"Worker Name"}
                                            value={values.workerName2}
                                            required={true}
                                            error={
                                                touched.workerName2 &&
                                                errors.workerName2
                                            }
                                        />
                                    </div>
                                    <div className="col-6">
                                        <ReactSignatureCanvas
                                            penColor="black"
                                            canvasProps={{
                                                className:
                                                    "sign101 border mt-1",
                                            }}
                                            ref={sigRef}
                                            onEnd={handleSignatureEnd}
                                        />
                                        <div className="d-block">
                                            <button
                                                type="button"
                                                className="btn btn-warning mb-2"
                                                onClick={clearSignature}
                                            >
                                                Clear
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <button type="submit" className="btn btn-success">
                                submit
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    );
};

export default SafeAndGear;
