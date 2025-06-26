//TaxExemption

import { useTranslation } from "react-i18next";
import CheckBox from "./inputElements/CheckBox";
import DateField from "./inputElements/DateField";
import SelectElement from "./inputElements/SelectElement";
import TextField from "./inputElements/TextField";
import { cityOption } from "./cityCountry";
import { handleHeicConvert } from "../../Utils/common.utils";

export default function TaxExemption({
    errors,
    values,
    touched,
    handleBlur,
    handleChange,
    setFieldValue,
    handleBubbleToggle,
    activeBubble
}) {
    const { t } = useTranslation();

    return (
        <div className="mt-4">
            <p className="navyblueColor font-24  font-w-500">{t("form101.taxExemption")}</p>

            {/* 1 */}
            <div className="pt-3">
                <CheckBox
                    name={"TaxExemption.isIsraelResident"}
                    label={t("form101.exm1")}
                    disabled
                    checked={values.employeeIsraeliResident === "Yes"}
                    onChange={handleChange}
                    onBlur={handleBlur}
                />
            </div>
            {/* 2 */}
            <div className="pt-3">
                <CheckBox
                    name={"TaxExemption.disabled"}
                    label={t("form101.exm2")}
                    checked={values.TaxExemption.disabled}
                    onChange={handleChange}
                    onBlur={handleBlur}
                    error={
                        touched.TaxExemption &&
                            errors.TaxExemption &&
                            touched.TaxExemption.disabled &&
                            errors.TaxExemption.disabled
                            ? errors.TaxExemption.disabled
                            : ""
                    }
                />
                {values.TaxExemption.disabled && (
                    <>
                        <div className="mb-2 mx-2">
                            <label
                                htmlFor="employeepassportCopy"
                                className="pt-2"
                            >
                                {t("form101.disabledCertificate")}
                            </label>
                            <br />
                            <div className="ml-5">
                                <div className="input_container">
                                    <input
                                        type="file"
                                        name="TaxExemption.disabledCertificate"
                                        id="employeepassportCopy"
                                        accept=".jpg,.jpeg,.png,.heic,.heif,image/*"  // explicitly include HEIC/HEIF
                                        onChange={async (e) => {
                                            e.persist();
                                            const originalFile = e.target.files[0];
                                            const processedFile = await handleHeicConvert(originalFile);
                                            setFieldValue(
                                                "TaxExemption.disabledCertificate",
                                                processedFile
                                            )
                                        }
                                        }
                                        onBlur={handleBlur}
                                    />
                                </div>
                                {touched.TaxExemption &&
                                    errors.TaxExemption &&
                                    touched.TaxExemption.disabledCertificate &&
                                    errors.TaxExemption.disabledCertificate && (
                                        <p className="text-danger">
                                            {
                                                errors.TaxExemption
                                                    .disabledCertificate
                                            }
                                        </p>
                                    )}
                            </div>
                            <div className="pt-2">
                                <CheckBox
                                    name={"TaxExemption.disabledCompensation"}
                                    label={t("form101.disabledCompensation")}
                                    checked={
                                        values.TaxExemption.disabledCompensation
                                    }
                                    onChange={handleChange}
                                    onBlur={handleBlur}
                                    error={
                                        touched.TaxExemption &&
                                            errors.TaxExemption &&
                                            touched.TaxExemption
                                                .disabledCompensation &&
                                            errors.TaxExemption.disabledCompensation
                                            ? errors.TaxExemption
                                                .disabledCompensation
                                            : ""
                                    }
                                />
                            </div>
                            {values.TaxExemption &&
                                values.TaxExemption.disabledCompensation && (
                                    <div className="row">
                                        <label htmlFor="disabledCompensationCertificate">
                                            {t(
                                                "form101.disabledCompensationCertificate"
                                            )}
                                        </label>
                                        <div className="ml-2">
                                            <div className="input_container">
                                                <input
                                                    type="file"
                                                    name="TaxExemption.disabledCompensationCertificate"
                                                    id="disabledCompensationCertificate"
                                                    accept=".jpg,.jpeg,.png,.heic,.heif,image/*"  // explicitly include HEIC/HEIF
                                                    onChange={async (e) => {
                                                        e.persist();
                                                        const originalFile = e.target.files[0];
                                                        const processedFile = await handleHeicConvert(originalFile);
                                                        setFieldValue(
                                                            "TaxExemption.disabledCompensationCertificate",
                                                            processedFile
                                                        )
                                                    }
                                                    }
                                                    onBlur={handleBlur}
                                                />
                                            </div>
                                            {touched.TaxExemption &&
                                                errors.TaxExemption &&
                                                touched.TaxExemption
                                                    .disabledCompensationCertificate &&
                                                errors.TaxExemption
                                                    .disabledCompensationCertificate && (
                                                    <p className="text-danger">
                                                        {
                                                            errors.TaxExemption
                                                                .disabledCompensationCertificate
                                                        }
                                                    </p>
                                                )}
                                        </div>
                                    </div>
                                )}
                        </div>
                    </>
                )}
            </div>
            {/* 3 */}
            <div className="pt-3">
                <CheckBox
                    name={"TaxExemption.exm3"}
                    label={t("form101.exm3")}
                    checked={values.TaxExemption.exm3}
                    onChange={handleChange}
                    onBlur={handleBlur}
                    error={
                        touched.TaxExemption &&
                            errors.TaxExemption &&
                            touched.TaxExemption.exm3 &&
                            errors.TaxExemption.exm3
                            ? errors.TaxExemption.exm3
                            : ""
                    }
                />
                {values.TaxExemption && values.TaxExemption.exm3 && (
                    <div className="row">
                        <div className=" col-md-4 col-sm-6 col-12">
                            <DateField
                                name="TaxExemption.exm3Date"
                                label={t("form101.label_from_date")}
                                value={values.TaxExemption.exm3Date}
                                onChange={handleChange}
                                onBlur={handleBlur}
                                error={
                                    touched.TaxExemption &&
                                        errors.TaxExemption &&
                                        touched.TaxExemption.exm3Date &&
                                        errors.TaxExemption.exm3Date
                                        ? errors.TaxExemption.exm3Date
                                        : ""
                                }
                                required
                            />
                        </div>
                        <div className="col-md-4 col-sm-6 col-12">
                            <SelectElement
                                name={"TaxExemption.exm3Locality"}
                                label={t("form101.Locality")}
                                value={values.TaxExemption.exm3Locality}
                                onChange={handleChange}
                                onBlur={handleBlur}
                                error={
                                    touched.TaxExemption &&
                                        errors.TaxExemption &&
                                        touched.TaxExemption.exm3Locality &&
                                        errors.TaxExemption.exm3Locality
                                        ? errors.TaxExemption.exm3Locality
                                        : ""
                                }
                                options={cityOption}
                                required={true}
                            />
                        </div>
                        <div className="col-12">
                            <label htmlFor="employeepassportCopy">
                                {t("form101.exm3Certificate")}
                            </label>
                            <br />
                            <div className="ml-5">
                                <div className="input_container">
                                    <input
                                        type="file"
                                        name="TaxExemption.exm3Certificate"
                                        id="exm3Certificate"
                                        accept=".jpg,.jpeg,.png,.heic,.heif,image/*"  // explicitly include HEIC/HEIF
                                        onChange={async (e) => {
                                            e.persist();
                                            const originalFile = e.target.files[0];
                                            const processedFile = await handleHeicConvert(originalFile);
                                            setFieldValue(
                                                "TaxExemption.exm3Certificate",
                                                processedFile
                                            )
                                        }
                                        }
                                        onBlur={handleBlur}
                                    />
                                </div>
                                {touched.TaxExemption &&
                                    errors.TaxExemption &&
                                    touched.TaxExemption.exm3Certificate &&
                                    errors.TaxExemption.exm3Certificate && (
                                        <p className="text-danger">
                                            {errors.TaxExemption.exm3Certificate}
                                        </p>
                                    )}
                            </div>
                        </div>
                    </div>
                )}
            </div>
            {/* 4 */}
            <div className="pt-3">
                <CheckBox
                    name={"TaxExemption.exm4"}
                    label={t("form101.exm4")}
                    checked={values.TaxExemption.exm4}
                    onChange={handleChange}
                    onBlur={handleBlur}
                    error={
                        touched.TaxExemption &&
                            errors.TaxExemption &&
                            touched.TaxExemption.exm4 &&
                            errors.TaxExemption.exm4
                            ? errors.TaxExemption.exm4
                            : ""
                    }
                />
                {values.TaxExemption && values.TaxExemption.exm4 && (
                    <div className="row">
                        {" "}
                        <div className="col-md-6 col-12">
                            <DateField
                                name="TaxExemption.exm4FromDate"
                                label={t("form101.label_from_date")}
                                value={values.TaxExemption.exm4FromDate}
                                onChange={handleChange}
                                onBlur={handleBlur}
                                error={
                                    touched.TaxExemption &&
                                        errors.TaxExemption &&
                                        touched.TaxExemption.exm4FromDate &&
                                        errors.TaxExemption.exm4FromDate
                                        ? errors.TaxExemption.exm4FromDate
                                        : ""
                                }
                                required
                            />
                        </div>
                        <div className="col-md-6 col-12">
                            <label htmlFor="exm4ImmigrationCertificate">
                                {t("form101.exm4ImmigrationCertificate")}
                            </label>
                            <br />
                            <div className="">
                                <div className="input_container">
                                    <input
                                        type="file"
                                        name="TaxExemption.exm4ImmigrationCertificate"
                                        id="exm4ImmigrationCertificate"
                                        accept=".jpg,.jpeg,.png,.heic,.heif,image/*"  // explicitly include HEIC/HEIF
                                        onChange={async (e) => {
                                            e.persist();
                                            const originalFile = e.target.files[0];
                                            const processedFile = await handleHeicConvert(originalFile);
                                            setFieldValue(
                                                "TaxExemption.exm4ImmigrationCertificate",
                                                processedFile
                                            )
                                        }
                                        }
                                        onBlur={handleBlur}
                                    />
                                </div>
                                {touched.TaxExemption &&
                                    errors.TaxExemption &&
                                    touched.TaxExemption
                                        .exm4ImmigrationCertificate &&
                                    errors.TaxExemption
                                        .exm4ImmigrationCertificate && (
                                        <p className="text-danger">
                                            {
                                                errors.TaxExemption
                                                    .exm4ImmigrationCertificate
                                            }
                                        </p>
                                    )}
                            </div>
                        </div>
                        <div className="col-12">
                            <DateField
                                name="TaxExemption.exm4NoIncomeDate"
                                id={"TaxExemption"}
                                label={t("form101.exm4NoIncomeDate")}
                                value={values.TaxExemption.exm4NoIncomeDate}
                                onChange={handleChange}
                                onBlur={handleBlur}
                                error={
                                    touched.TaxExemption &&
                                        errors.TaxExemption &&
                                        touched.TaxExemption.exm4NoIncomeDate &&
                                        errors.TaxExemption.exm4NoIncomeDate
                                        ? errors.TaxExemption.exm4NoIncomeDate
                                        : ""
                                }
                                required
                            />
                            <p>{t("form101.exm4entitlemente")}</p>
                        </div>
                    </div>
                )}
            </div>
            {/* 5 */}
            <div className="pt-3">
                <CheckBox
                    name={"TaxExemption.exm5"}
                    label={t("form101.exm5")}
                    checked={values.TaxExemption.exm5}
                    onChange={handleChange}
                    onBlur={handleBlur}
                    disabled={values.employeeMaritalStatus !== "Married"}
                    error={
                        touched.TaxExemption &&
                            errors.TaxExemption &&
                            touched.TaxExemption.exm5 &&
                            errors.TaxExemption.exm5
                            ? errors.TaxExemption.exm5
                            : ""
                    }
                />
                {values.employeeMaritalStatus !== "Married" && (
                    <p className="text-danger">{t("form101.exm5NOte")}</p>
                )}
                {values.TaxExemption && values.TaxExemption.exm5 && (
                    <div>
                        <div className="d-flex gap-5">
                            <div>
                                <label htmlFor="exm5disabledCirtificate">
                                    {t("form101.exm5disabledCirtificate")}
                                </label>
                                <br />
                                <div className="ml-5">
                                    <div className="input_container">
                                        <input
                                            type="file"
                                            name="TaxExemption.exm5disabledCirtificate"
                                            id="exm5disabledCirtificate"
                                            accept=".jpg,.jpeg,.png,.heic,.heif,image/*"  // explicitly include HEIC/HEIF
                                            onChange={async (e) => {
                                                e.persist();
                                                const originalFile = e.target.files[0];
                                                const processedFile = await handleHeicConvert(originalFile);
                                                setFieldValue(
                                                    "TaxExemption.exm5disabledCirtificate",
                                                    processedFile
                                                )
                                            }
                                            }
                                            onBlur={handleBlur}
                                        />
                                    </div>
                                    {touched.TaxExemption &&
                                        errors.TaxExemption &&
                                        touched.TaxExemption
                                            .exm5disabledCirtificate &&
                                        errors.TaxExemption
                                            .exm5disabledCirtificate && (
                                            <p className="text-danger">
                                                {
                                                    errors.TaxExemption
                                                        .exm5disabledCirtificate
                                                }
                                            </p>
                                        )}
                                </div>
                            </div>
                        </div>
                    </div>
                )}
            </div>
            {/* 6 remaining*/}
            <div className="pt-3">
                <CheckBox
                    name={"TaxExemption.exm6"}
                    label={t("form101.exm6")}
                    checked={values.TaxExemption.exm6}
                    onChange={handleChange}
                    onBlur={handleBlur}
                    disabled={
                        !Array.isArray(values.children) ||
                        values.children?.length === 0 ||
                        errors.children?.length > 0
                    }
                    error={
                        touched.TaxExemption &&
                            errors.TaxExemption &&
                            touched.TaxExemption.exm6 &&
                            errors.TaxExemption.exm6
                            ? errors.TaxExemption.exm6
                            : ""
                    }
                />
                <p className="text-secondary">{t("form101.exm6Note")}</p>

                {/* {Array.isArray(values.children) &&
                    (values.children?.length === 0 ||
                        errors.children?.length > 0) && (
                        <p className="text-danger">
                            It is not possible to choose because you have not
                            specified children.
                        </p>
                    )}
                {Array.isArray(values.children) &&
                    values.children?.length > 0 &&
                    errors.children?.length === 0 &&
                    values.TaxExemption &&
                    values.TaxExemption.exm6 && (
                        <div>
                            <div className="d-flex gap-5">
                                <div>
                                    <label htmlFor="exm5disabledCirtificate">
                                        Disabled or blind certificate for the
                                        employee or the spouse.
                                    </label>
                                    <br />
                                    <input
                                        type="file"
                                        name="TaxExemption.exm6disabledCirtificate"
                                        id="exm5disabledCirtificate"
                                        accept="image/*"
                                        onChange={(e) =>
                                            setFieldValue(
                                                "TaxExemption.exm5disabledCirtificate",
                                                e.target.files[0]
                                            )
                                        }
                                        onBlur={handleBlur}
                                    />
                                    {touched.TaxExemption &&
                                        errors.TaxExemption &&
                                        touched.TaxExemption
                                            .exm5disabledCirtificate &&
                                        errors.TaxExemption
                                            .exm5disabledCirtificate && (
                                            <p className="text-danger">
                                                {
                                                    errors.TaxExemption
                                                        .exm5disabledCirtificate
                                                }
                                            </p>
                                        )}
                                </div>
                            </div>
                        </div>
                    )} */}
            </div>
            {/* 7*/}
            <div className="pt-3">
                <CheckBox
                    name={"TaxExemption.exm7"}
                    label={t("form101.exm7")}
                    checked={values.TaxExemption.exm7}
                    onChange={handleChange}
                    onBlur={handleBlur}
                    disabled={
                        !Array.isArray(values.children) ||
                        values.children?.length === 0 ||
                        errors.children?.length > 0
                    }
                    error={
                        touched.TaxExemption &&
                            errors.TaxExemption &&
                            touched.TaxExemption.exm7 &&
                            errors.TaxExemption.exm7
                            ? errors.TaxExemption.exm7
                            : ""
                    }
                />
                <p className="text-secondary">{t("form101.exm7Note")}</p>

                {((Array.isArray(values.children) &&
                    values.children?.length > 0 &&
                    errors.children?.length === 0 &&
                    values.TaxExemption &&
                    values.TaxExemption.exm7) ||
                    (!errors?.children && values.children?.length > 0)) && (
                        <div>
                            <div className="row mt-3">
                                <div className="col-4">
                                    <TextField
                                        name="TaxExemption.exm7NoOfChild"
                                        label={t("form101.exm7NoOfChild")}
                                        value={values.TaxExemption.exm7NoOfChild}
                                        onChange={handleChange}
                                        onBlur={handleBlur}
                                        error={
                                            touched.TaxExemption &&
                                                errors.TaxExemption &&
                                                touched.TaxExemption.exm7NoOfChild &&
                                                errors.TaxExemption.exm7NoOfChild
                                                ? errors.TaxExemption.exm7NoOfChild
                                                : ""
                                        }
                                        required
                                    />
                                </div>
                                <div className="col-4">
                                    <TextField
                                        name="TaxExemption.exm7NoOfChild1to5"
                                        label={t("form101.exm7NoOfChild1to5")}
                                        value={
                                            values.TaxExemption.exm7NoOfChild1to5
                                        }
                                        onChange={handleChange}
                                        onBlur={handleBlur}
                                        error={
                                            touched.TaxExemption &&
                                                errors.TaxExemption &&
                                                touched.TaxExemption
                                                    .exm7NoOfChild1to5 &&
                                                errors.TaxExemption.exm7NoOfChild1to5
                                                ? errors.TaxExemption
                                                    .exm7NoOfChild1to5
                                                : ""
                                        }
                                        required
                                    />
                                </div>
                                <div className="col-4">
                                    <TextField
                                        name="TaxExemption.exm7NoOfChild6to17"
                                        label={t("form101.exm7NoOfChild6to17")}
                                        value={
                                            values.TaxExemption.exm7NoOfChild6to17
                                        }
                                        onChange={handleChange}
                                        onBlur={handleBlur}
                                        error={
                                            touched.TaxExemption &&
                                                errors.TaxExemption &&
                                                touched.TaxExemption
                                                    .exm7NoOfChild6to17 &&
                                                errors.TaxExemption.exm7NoOfChild6to17
                                                ? errors.TaxExemption
                                                    .exm7NoOfChild6to17
                                                : ""
                                        }
                                        required
                                    />
                                </div>
                                <div className="col-4">
                                    <TextField
                                        name="TaxExemption.exm7NoOfChild18"
                                        label={t("form101.exm7NoOfChild18")}
                                        value={values.TaxExemption.exm7NoOfChild18}
                                        onChange={handleChange}
                                        onBlur={handleBlur}
                                        error={
                                            touched.TaxExemption &&
                                                errors.TaxExemption &&
                                                touched.TaxExemption.exm7NoOfChild18 &&
                                                errors.TaxExemption.exm7NoOfChild18
                                                ? errors.TaxExemption
                                                    .exm7NoOfChild18
                                                : ""
                                        }
                                        required
                                    />
                                </div>
                            </div>
                        </div>
                    )}
            </div>
            {/* 8*/}
            <div className="pt-3">
                <CheckBox
                    name={"TaxExemption.exm8"}
                    label={t("form101.exm8")}
                    checked={values.TaxExemption.exm8}
                    onChange={handleChange}
                    onBlur={handleBlur}
                    disabled={values.TaxExemption.exm7}
                    error={
                        touched.TaxExemption &&
                            errors.TaxExemption &&
                            touched.TaxExemption.exm8 &&
                            errors.TaxExemption.exm8
                            ? errors.TaxExemption.exm8
                            : ""
                    }
                />
                <p className="text-secondary">{t("form101.exm8Note")}</p>

                {((Array.isArray(values.children) &&
                    values.children?.length > 0 &&
                    errors.children?.length === 0 &&
                    values.TaxExemption &&
                    values.TaxExemption.exm8) ||
                    (!errors?.children && values.children?.length > 0)) && (
                        <div>
                            <div className="row mt-3">
                                <div className="col-4">
                                    <TextField
                                        name="TaxExemption.exm8NoOfChild"
                                        label={t("form101.exm7NoOfChild")}
                                        value={values.TaxExemption.exm8NoOfChild}
                                        onChange={handleChange}
                                        onBlur={handleBlur}
                                        error={
                                            touched.TaxExemption &&
                                                errors.TaxExemption &&
                                                touched.TaxExemption.exm8NoOfChild &&
                                                errors.TaxExemption.exm8NoOfChild
                                                ? errors.TaxExemption.exm8NoOfChild
                                                : ""
                                        }
                                        required
                                    />
                                </div>
                                <div className="col-4">
                                    <TextField
                                        name="TaxExemption.exm8NoOfChild1to5"
                                        label={t("form101.exm7NoOfChild1to5")}
                                        value={
                                            values.TaxExemption.exm8NoOfChild1to5
                                        }
                                        onChange={handleChange}
                                        onBlur={handleBlur}
                                        error={
                                            touched.TaxExemption &&
                                                errors.TaxExemption &&
                                                touched.TaxExemption
                                                    .exm8NoOfChild1to5 &&
                                                errors.TaxExemption.exm8NoOfChild1to5
                                                ? errors.TaxExemption
                                                    .exm8NoOfChild1to5
                                                : ""
                                        }
                                        required
                                    />
                                </div>
                                <div className="col-4">
                                    <TextField
                                        name="TaxExemption.exm8NoOfChild6to17"
                                        label={t("form101.exm7NoOfChild6to17")}
                                        value={
                                            values.TaxExemption.exm8NoOfChild6to17
                                        }
                                        onChange={handleChange}
                                        onBlur={handleBlur}
                                        error={
                                            touched.TaxExemption &&
                                                errors.TaxExemption &&
                                                touched.TaxExemption
                                                    .exm8NoOfChild6to17 &&
                                                errors.TaxExemption.exm8NoOfChild6to17
                                                ? errors.TaxExemption
                                                    .exm8NoOfChild6to17
                                                : ""
                                        }
                                        required
                                    />
                                </div>
                            </div>
                        </div>
                    )}
            </div>
            {/* 9*/}
            <div className="pt-3">
                <CheckBox
                    name={"TaxExemption.exm9"}
                    label={t("form101.exm9")}
                    checked={values.TaxExemption.exm9}
                    onChange={handleChange}
                    onBlur={handleBlur}
                    disabled={values.TaxExemption.exm7}
                    error={
                        touched.TaxExemption &&
                            errors.TaxExemption &&
                            touched.TaxExemption.exm9 &&
                            errors.TaxExemption.exm9
                            ? errors.TaxExemption.exm9
                            : ""
                    }
                />
            </div>
            {/* 10 */}
            <div className="pt-3">
                <CheckBox
                    name={"TaxExemption.exm10"}
                    label={t("form101.exm10")}
                    checked={values.TaxExemption.exm10}
                    onChange={handleChange}
                    onBlur={handleBlur}
                    error={
                        touched.TaxExemption &&
                            errors.TaxExemption &&
                            touched.TaxExemption.exm10 &&
                            errors.TaxExemption.exm10
                            ? errors.TaxExemption.exm10
                            : ""
                    }
                />
                <p className="text-secondary">{t("form101.exm10Note")}</p>
                {values.TaxExemption.exm10 && (
                    <>
                        <div className="mb-2 mx-2">
                            <label
                                htmlFor="employeepassportCopy"
                                className="pt-2"
                            >
                                {t("form101.employeepassportCopy")}
                            </label>
                            <br />
                            <div className="ml-5">
                                <div className="input_container">
                                    <input
                                        type="file"
                                        name="TaxExemption.exm10Certificate"
                                        id="employeepassportCopy"
                                        accept=".jpg,.jpeg,.png,.heic,.heif,image/*"  // explicitly include HEIC/HEIF
                                        onChange={async (e) => {
                                            e.persist();
                                            const originalFile = e.target.files[0];
                                            const processedFile = await handleHeicConvert(originalFile);
                                            setFieldValue(
                                                "TaxExemption.exm10Certificate",
                                                processedFile
                                            )
                                        }
                                        }
                                        onBlur={handleBlur}
                                    />
                                </div>
                                {touched.TaxExemption &&
                                    errors.TaxExemption &&
                                    touched.TaxExemption.exm10Certificate &&
                                    errors.TaxExemption.exm10Certificate && (
                                        <p className="text-danger">
                                            {errors.TaxExemption.exm10Certificate}
                                        </p>
                                    )}
                            </div>
                        </div>
                    </>
                )}
            </div>
            {/* 11 */}
            <div className="pt-3">
                <CheckBox
                    name={"TaxExemption.exm11"}
                    label={t("form101.exm11")}
                    checked={values.TaxExemption.exm11}
                    onChange={handleChange}
                    onBlur={handleBlur}
                    error={
                        touched.TaxExemption &&
                            errors.TaxExemption &&
                            touched.TaxExemption.exm11 &&
                            errors.TaxExemption.exm11
                            ? errors.TaxExemption.exm11
                            : ""
                    }
                />
                <p className="text-secondary">
                    {t("form101.spouseNotReceiveTaxCredit")}
                </p>
                {values.TaxExemption.exm11 && (
                    <>
                        <TextField
                            name="TaxExemption.exm11NoOfChildWithDisibility"
                            label={t("form101.exm11NoOfChildWithDisibility")}
                            value={
                                values.TaxExemption.exm11NoOfChildWithDisibility
                            }
                            onChange={handleChange}
                            onBlur={handleBlur}
                            error={
                                touched.TaxExemption &&
                                    errors.TaxExemption &&
                                    touched.TaxExemption
                                        .exm11NoOfChildWithDisibility &&
                                    errors.TaxExemption.exm11NoOfChildWithDisibility
                                    ? errors.TaxExemption
                                        .exm11NoOfChildWithDisibility
                                    : ""
                            }
                            required
                        />
                        <div className="mb-2 mx-2">
                            <label
                                htmlFor="TaxExemption.exm11Certificate"
                                className="pt-2"
                            >
                                {t("form101.exm11Certificate")}
                            </label>
                            <br />
                            <div className="ml-5">
                                <div className="input_container">
                                    <input
                                        type="file"
                                        name="TaxExemption.exm11Certificate"
                                        id="TaxExemption.exm11Certificate"
                                        accept=".jpg,.jpeg,.png,.heic,.heif,image/*"  // explicitly include HEIC/HEIF
                                        onChange={async (e) => {
                                            e.persist();
                                            const originalFile = e.target.files[0];
                                            const processedFile = await handleHeicConvert(originalFile);
                                            setFieldValue(
                                                "TaxExemption.exm11Certificate",
                                                processedFile
                                            )
                                        }
                                        }
                                        onBlur={handleBlur}
                                    />
                                </div>
                                {touched.TaxExemption &&
                                    errors.TaxExemption &&
                                    touched.TaxExemption.exm11Certificate &&
                                    errors.TaxExemption.exm11Certificate && (
                                        <p className="text-danger">
                                            {errors.TaxExemption.exm11Certificate}
                                        </p>
                                    )}
                            </div>
                        </div>
                    </>
                )}
            </div>
            {/* 12 */}
            <div className="pt-3">
                <CheckBox
                    name={"TaxExemption.exm12"}
                    label={t("form101.exm12")}
                    checked={values.TaxExemption.exm12}
                    onChange={handleChange}
                    onBlur={handleBlur}
                    error={
                        touched.TaxExemption &&
                            errors.TaxExemption &&
                            touched.TaxExemption.exm12 &&
                            errors.TaxExemption.exm12
                            ? errors.TaxExemption.exm12
                            : ""
                    }
                />
                <p className="text-secondary">{t("form101.exm12Note")}</p>
                {values.TaxExemption.exm12 && (
                    <>
                        <div className="mb-2 mx-2">
                            <label
                                htmlFor="employeepassportCopy"
                                className="pt-2"
                            >
                                {t("form101.exm12Certificate")}
                            </label>
                            <br />
                            <div className="ml-5">
                                <div className="input_container">
                                    <input
                                        type="file"
                                        name="TaxExemption.exm12Certificate"
                                        id="TaxExemption.exm12Certificate"
                                        accept=".jpg,.jpeg,.png,.heic,.heif,image/*"  // explicitly include HEIC/HEIF
                                        onChange={async (e) => {
                                            e.persist();
                                            const originalFile = e.target.files[0];
                                            const processedFile = await handleHeicConvert(originalFile);
                                            setFieldValue(
                                                "TaxExemption.exm12Certificate",
                                                processedFile
                                            )
                                        }
                                        }
                                        onBlur={handleBlur}
                                    />
                                </div>
                                {touched.TaxExemption &&
                                    errors.TaxExemption &&
                                    touched.TaxExemption.exm12Certificate &&
                                    errors.TaxExemption.exm12Certificate && (
                                        <p className="text-danger">
                                            {errors.TaxExemption.exm12Certificate}
                                        </p>
                                    )}
                            </div>
                        </div>
                    </>
                )}
            </div>
            {/* 13 */}
            <div className="pt-3">
                <CheckBox
                    name={"TaxExemption.exm13"}
                    label={t("form101.exm13")}
                    checked={values.TaxExemption.exm13}
                    onChange={handleChange}
                    onBlur={handleBlur}
                    disabled={
                        !values.Spouse ||
                        values.Spouse.Dob == "" ||
                        values.employeeDob === ""
                    }
                    error={
                        touched.TaxExemption &&
                            errors.TaxExemption &&
                            touched.TaxExemption.exm13 &&
                            errors.TaxExemption.exm13
                            ? errors.TaxExemption.exm13
                            : ""
                    }
                />
                <p className="text-secondary">{t("form101.exm12Note")}</p>
            </div>
            {/* 14 */}
            <div className="pt-3">
                <CheckBox
                    name={"TaxExemption.exm14"}
                    label={t("form101.exm14")}
                    checked={values.TaxExemption.exm14}
                    onChange={handleChange}
                    onBlur={handleBlur}
                    error={
                        touched.TaxExemption &&
                            errors.TaxExemption &&
                            touched.TaxExemption.exm14 &&
                            errors.TaxExemption.exm14
                            ? errors.TaxExemption.exm14
                            : ""
                    }
                />
                <p className="text-secondary">{t("form101.exm12Note")}</p>
                {values.TaxExemption.exm14 && (
                    <div className="row">
                        <div className="col-lg-4 col-md-6 col-12">
                            <DateField
                                name="TaxExemption.exm14BeginingDate"
                                label={t("form101.exm14BeginingDate")}
                                value={values.TaxExemption.exm14BeginingDate}
                                onChange={handleChange}
                                onBlur={handleBlur}
                                error={
                                    touched.TaxExemption &&
                                        errors.TaxExemption &&
                                        touched.TaxExemption.exm14BeginingDate &&
                                        errors.TaxExemption.exm14BeginingDate
                                        ? errors.TaxExemption.exm14BeginingDate
                                        : ""
                                }
                                required
                            />
                        </div>
                        <div className="col-lg-4 col-md-6 col-12">
                            <DateField
                                name="TaxExemption.exm14EndDate"
                                label={t("form101.exm14EndDate")}
                                value={values.TaxExemption.exm14EndDate}
                                onChange={handleChange}
                                onBlur={handleBlur}
                                error={
                                    touched.TaxExemption &&
                                        errors.TaxExemption &&
                                        touched.TaxExemption.exm14EndDate &&
                                        errors.TaxExemption.exm14EndDate
                                        ? errors.TaxExemption.exm14EndDate
                                        : ""
                                }
                                required
                            />
                        </div>
                        <div className="col-lg-4 col-md-6 col-12">
                            <label
                                htmlFor="TaxExemption.exm14Certificate"
                                className="pt-2"
                            >
                                {t("form101.exm14Certificate")}
                            </label>
                            <br />
                            <div className="">
                                <div className="input_container">
                                    <input
                                        type="file"
                                        name="TaxExemption.exm14Certificate"
                                        id="TaxExemption.exm14Certificate"
                                        accept=".jpg,.jpeg,.png,.heic,.heif,image/*"  // explicitly include HEIC/HEIF
                                        onChange={async (e) => {
                                            e.persist();
                                            const originalFile = e.target.files[0];
                                            const processedFile = await handleHeicConvert(originalFile);
                                            setFieldValue(
                                                "TaxExemption.exm14Certificate",
                                                processedFile
                                            )
                                        }
                                        }
                                        onBlur={handleBlur}
                                    />
                                </div>
                                {touched.TaxExemption &&
                                    errors.TaxExemption &&
                                    touched.TaxExemption.exm14Certificate &&
                                    errors.TaxExemption.exm14Certificate && (
                                        <p className="text-danger">
                                            {errors.TaxExemption.exm14Certificate}
                                        </p>
                                    )}
                            </div>
                        </div>
                    </div>
                )}
            </div>
            {/* 15 */}
            <div className="pt-3">
                <CheckBox
                    name={"TaxExemption.exm15"}
                    label={t("form101.exm15")}
                    checked={values.TaxExemption.exm15}
                    onChange={handleChange}
                    onBlur={handleBlur}
                    error={
                        touched.TaxExemption &&
                            errors.TaxExemption &&
                            touched.TaxExemption.exm15 &&
                            errors.TaxExemption.exm15
                            ? errors.TaxExemption.exm15
                            : ""
                    }
                />
                <p className="text-secondary">{t("form101.exm12Note")}</p>
                {values.TaxExemption.exm15 && (
                    <>
                        <div className="mb-2 mx-2">
                            <label
                                htmlFor="TaxExemption.exm15Certificate"
                                className="pt-2"
                            >
                                {t("form101.exm15Certificate")}
                            </label>
                            <br />
                            <div className="ml-5">
                                <div className="input_container">
                                    <input
                                        type="file"
                                        name="TaxExemption.exm15Certificate"
                                        id="TaxExemption.exm15Certificate"
                                        accept=".jpg,.jpeg,.png,.heic,.heif,image/*"  // explicitly include HEIC/HEIF
                                        onChange={async (e) => {
                                            e.persist();
                                            const originalFile = e.target.files[0];
                                            const processedFile = await handleHeicConvert(originalFile);
                                            setFieldValue(
                                                "TaxExemption.exm15Certificate",
                                                processedFile
                                            )
                                        }
                                        }
                                        onBlur={handleBlur}
                                    />
                                </div>
                                {touched.TaxExemption &&
                                    errors.TaxExemption &&
                                    touched.TaxExemption.exm15Certificate &&
                                    errors.TaxExemption.exm15Certificate && (
                                        <p className="text-danger">
                                            {errors.TaxExemption.exm15Certificate}
                                        </p>
                                    )}
                            </div>
                        </div>
                    </>
                )}
            </div>
        </div>
    );
}
